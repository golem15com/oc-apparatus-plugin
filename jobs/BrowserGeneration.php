<?php

namespace Golem15\Apparatus\Jobs;

use Log;
use Golem15\Apparatus\Classes\JobManager;
use Golem15\Apparatus\Contracts\ApparatusQueueJob;
use Golem15\Apparatus\ValueObjects\GenerationOptions;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class BrowserGeneration implements ApparatusQueueJob
{
    private int $jobId;
    private GenerationOptions $options;

    public function assignJobId(int $id)
    {
        $this->jobId = $id;
    }

    public function __construct(GenerationOptions $options)
    {
        $this->options = $options;
    }

    public function handle(JobManager $jobManager)
    {
        $jobManager->startJob($this->jobId, 1);

        $chromePath = env('CHROMIUM_PATH', '/usr/bin/chromium');
        if (!$this->preflightChecks($jobManager, $chromePath)) {
            return;
        }

        $tmpProfile = storage_path('app/chrome-pdf/' . $this->jobId);
        if (!is_dir($tmpProfile) && !mkdir($tmpProfile, 0775, true) && !is_dir($tmpProfile)) {
            $error = "Cannot create temp profile: {$tmpProfile}";
            Log::error($error);
            $jobManager->failJob($this->jobId, ['error' => $error]);
            return;
        }
        $this->options->flags[] = "--user-data-dir={$tmpProfile}";
        $timeoutSeconds = $this->options->timeout;

        // Use conservative flags that work broadly
        $flags = $this->options->flags;

        $cmd = array_merge([$chromePath], $flags, [$this->options->url]);

        $process = new Process($cmd);
        $process->setTimeout($timeoutSeconds);

        Log::info("Launching Chromium with flags: " . implode(' ', array_map(function ($s) {
                return str_contains($s, ' ') ? "\"{$s}\"" : $s;
        }, $flags)));

        try {
            // Stream output while running
            $process->run(function (string $type, string $buffer) {
                $line = trim($buffer);
                if ($line === '') {
                    return;
                }
                if ($type === Process::ERR) {
                    Log::warning("Chromium[stderr]: {$line}");
                } else {
                    Log::info("Chromium[stdout]: {$line}");
                }
            });

            if (!$process->isSuccessful()) {
                $stderr = trim($process->getErrorOutput());
                $stdout = trim($process->getOutput());
                $code   = $process->getExitCode();
                $error    = "Chromium exit {$code}. STDERR: {$stderr} STDOUT: {$stdout}";
                Log::error($error);
                $jobManager->failJob($this->jobId, ['error' => $error]);
                return;
            }

            if (!is_file($this->options->path) || filesize($this->options->path) === 0) {
                $error = "Success reported but PDF missing/empty at {$this->options->path}";
                Log::error($error);
                $jobManager->failJob($this->jobId, ['error' => $error]);
                return;
            }

            Log::info("PDF written OK (" . filesize($this->options->path) . " bytes)");
            $file = new File();
            $file->is_public = false;
            $file->fromFile($this->options->path);
            $file->save();
            $jobManager->completeJob($this->jobId, ['file' => $file->toArray()]);
            event('apparatus.media.generated', [$file, $this->jobId]);
        } catch (ProcessTimedOutException $e) {
            $error = "Chromium timed out after {$timeoutSeconds}s: " . $e->getMessage();
            Log::error($error);
            $jobManager->failJob($this->jobId, ['error' => $error]);
        } catch (\Throwable $e) {
            $error = "Chromium threw: " . $e->getMessage();
            Log::error($error . ' ' . $e->getTraceAsString());
            $jobManager->failJob($this->jobId, ['error' => $error]);
        } finally {
            $this->cleanupDir($tmpProfile);
        }
    }

    private function preflightChecks(JobManager $jobManager, mixed $chromePath): bool
    {
        if (!is_file($chromePath) || !is_executable($chromePath)) {
            $error = "Chromium binary not found or is invalid.";
            Log::error($error);
            $jobManager->failJob($this->jobId, ['error' => $error]);
            return false;
        }

        // Ensure output dir is writable
        $outDir = dirname($this->options->path);
        if (!is_dir($outDir) && !mkdir($outDir, 0775, true) && !is_dir($outDir)) {
            $error = "Cannot create output dir: {$outDir}";
            Log::error($error);
            $jobManager->failJob($this->jobId, ['error' => $error]);
            return false;
        }
        if (!is_writable($outDir)) {
            $error = "Output dir not writable: {$outDir}";
            Log::error($error);
            $jobManager->failJob($this->jobId, ['error' => $error]);
            return false;
        }
        return true;
    }

    private function cleanupDir(string $dir)
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $p = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($p) && !is_link($p)) $this->cleanupDir($p);
            else @unlink($p);
        }
        @rmdir($dir);
    }
}
