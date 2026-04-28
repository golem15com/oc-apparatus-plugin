<?php
/**
 * Created by PhpStorm.
 * User: jin
 * Date: 3/10/18
 * Time: 2:45 PM
 */

namespace Golem15\Apparatus\Classes;

/**
 * Class RequestSender
 * @package Golem15\Apparatus\Classes
 */
class RequestSender
{
    private $headers = [];
    /**
     * RequestSender constructor.
     *
     * @param string      $contentType
     * @param string|null $bearerToken
     */
public function __construct($bearerToken = null, $contentType = 'application/json')
    {
        $this->headers[] = 'Content-Type: '.$contentType;
        if ($bearerToken) {
            $this->headers[] = 'Authorization: Bearer '.$bearerToken;
        }
    }

    /**
     * @param array  $data
     * @param string $url
     * @return array|bool
     */
    public function sendPostRequest(array $data, string $url, bool $asJson = false)
    {
        if($asJson){
            $data = json_encode($data);
        } else {
            $data = http_build_query($data);
        }
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }

    public function sendPostRequestWithFile(array $data, string $url, string $filePath, bool $asJson = false, ?string $mimeType = null, ?string $fileName = null)
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        // Multipart uploads require array data — ignore $asJson to avoid breaking cURL
        $postFields = $data;
        $postFields['file'] = new \CURLFile(
            $filePath,
            $mimeType ?? mime_content_type($filePath) ?: 'application/octet-stream',
            $fileName ?? basename($filePath)
        );

        // Filter out Content-Type header so cURL auto-generates multipart boundary
        $headers = array_filter($this->headers, function ($header) {
            return stripos($header, 'Content-Type:') !== 0;
        });

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }

    public function sendPutRequest(array $data, $url, bool $asJson = false)
    {
        if($asJson){
            $data = json_encode($data);
        } else {
            $data = http_build_query($data);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * Validate URL to prevent SSRF attacks by blocking private/reserved IP ranges.
     *
     * @param string $url
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateUrl(string $url): void
    {
        $parsed = parse_url($url);
        if ($parsed === false || empty($parsed['host'])) {
            throw new \InvalidArgumentException('URL must contain a valid host');
        }
        $host = $parsed['host'];

        // parse_url keeps IPv6 hosts wrapped in brackets, e.g. "[::1]"
        if (strlen($host) >= 2 && $host[0] === '[' && substr($host, -1) === ']') {
            $host = substr($host, 1, -1);
        }

        // If host is already an IP literal (v4 or v6), validate it directly --
        // gethostbyname() returns IP literals unchanged, which would otherwise bypass the filter below.
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                throw new \InvalidArgumentException('URL resolves to a private or reserved IP address');
            }
            return;
        }

        // Resolve to A and AAAA records; reject if any address is private/reserved.
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (!empty($records)) {
            foreach ($records as $record) {
                $ip = $record['ip'] ?? $record['ipv6'] ?? null;
                if ($ip === null) {
                    continue;
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                    throw new \InvalidArgumentException('URL resolves to a private or reserved IP address');
                }
            }
            return;
        }

        // Fall back to gethostbyname (IPv4-only) when dns_get_record yields nothing.
        $ip = gethostbyname($host);
        if ($ip === $host) {
            // Unresolvable host -- preserve previous behavior of allowing it through.
            return;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            throw new \InvalidArgumentException('URL resolves to a private or reserved IP address');
        }
    }

    /**
     * @param array  $data
     * @param string $url
     * @return array|bool
     */
    public function sendGetRequest(array $data, string $url, $ignoreSsl = false)
    {
        $this->validateUrl($url);

        if ($ignoreSsl && !config('app.debug')) {
            $ignoreSsl = false;
            \Log::warning('RequestSender: SSL verification bypass blocked in production');
        }

        $error = false;
        $ch = curl_init();
        $query = http_build_query($data);
        if ($query) {
            $url .= '?'.$query;
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if($ignoreSsl){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        $content = curl_exec($ch);
        if (curl_errno($ch)) {
            $error = curl_error($ch);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'code'    => $httpCode,
            'content' => $content,
            'error'   => $error,
        ];
    }

    public function downloadFile(array $data, string $url, $ignoreSsl = false)
    {
        $this->validateUrl($url);

        if ($ignoreSsl && !config('app.debug')) {
            $ignoreSsl = false;
            \Log::warning('RequestSender: SSL verification bypass blocked in production');
        }

        $error = false;
        $httpCode = 0;
        $query = http_build_query($data);
        if ($query) {
            $url .= '?'.$query;
        }
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        $fileName = basename($path);
        if (empty($fileName) || $fileName === '.' || $fileName === '..') {
            throw new \InvalidArgumentException('Invalid filename in URL');
        }
        $saveLocation = storage_path('app/uploads/private/'.$fileName);

        $ch = null;
        $fp = null;
        try {
            $fp = fopen($saveLocation, 'wb');
            if ($fp === false) {
                throw new \RuntimeException('Unable to open file for writing: '.$saveLocation);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            if ($ignoreSsl) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            }
            curl_exec($ch);
            if (curl_errno($ch)) {
                $error = curl_error($ch);
            }
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        } finally {
            if ($ch !== null) {
                curl_close($ch);
            }
            if (is_resource($fp)) {
                fclose($fp);
            }
        }

        // Remove a partially written file if the transfer failed
        if ($error !== false && file_exists($saveLocation)) {
            @unlink($saveLocation);
        }

        return [
            'code'    => $httpCode,
            'content' => $saveLocation,
            'error'   => $error,
        ];
    }

    /**
     * Send PATCH request
     *
     * @param array  $data
     * @param string $url
     * @param bool   $asJson
     * @param bool   $returnArray Return array with code/content/error (default: true)
     * @return array|string
     */
    public function sendPatchRequest(array $data, string $url, bool $asJson = true, bool $returnArray = true)
    {
        if($asJson){
            $data = json_encode($data);
        } else {
            $data = http_build_query($data);
        }
        $error = false;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $content = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($returnArray) {
            return [
                'code'    => $httpCode,
                'content' => $content,
                'error'   => $error,
            ];
        }

        return $content;
    }

    public function addHeader($header)
    {
        $this->headers[] = $header;
    }
}
