<?php namespace Golem15\Apparatus\Console\Concerns;

use Cache;
use Schema;

/**
 * Shared locale handling for the apparatus:mail-* commands.
 *
 * Mail templates are translatable when a Translate plugin is installed, and their
 * translations live in winter_translate_attributes rather than in the
 * system_mail_templates columns. The base column always holds the DEFAULT locale's
 * value -- which is Polish on one site and English on another -- so nothing here may
 * assume a language. We serialise "the default locale" plus "named locales".
 *
 * Everything is duck-typed and guarded: Apparatus does not depend on a Translate
 * plugin, and on a site without one every method here degrades to "no locales",
 * leaving the commands behaving exactly as they did before.
 *
 * @package Golem15\Apparatus\Console\Concerns
 * @author Golem15
 */
trait HandlesMailLocales
{
    /**
     * Attributes carried in a locale file, in the order they are written.
     *
     * @var array
     */
    protected $localeAttributes = ['subject', 'description', 'content_text', 'content_html'];

    /**
     * Cached locale lookup, so we hit the locales table once per command run.
     *
     * @var array|null
     */
    protected $localeCache = null;

    /**
     * Resolve the installed Translate plugin's Locale model, if there is one.
     *
     * Golem15.Translate replaces Winter.Translate and both class names may resolve,
     * so this prefers the Golem15 namespace.
     *
     * @return string|null
     */
    protected function getLocaleModelClass()
    {
        foreach ([
            \Golem15\Translate\Models\Locale::class,
            \Winter\Translate\Models\Locale::class,
            \RainLab\Translate\Models\Locale::class,
        ] as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * All known locales as [code => isDefault].
     *
     * Deliberately reads every locale rather than only the enabled ones: a locale can
     * be authored and version-controlled long before it is switched on for visitors,
     * which is exactly the state this project is in.
     *
     * @return array
     */
    protected function getMailLocales()
    {
        if ($this->localeCache !== null) {
            return $this->localeCache;
        }

        $this->localeCache = [];

        $class = $this->getLocaleModelClass();

        if (!$class || !Schema::hasTable('winter_translate_locales')) {
            return $this->localeCache;
        }

        try {
            foreach ($class::all() as $locale) {
                $this->localeCache[$locale->code] = (bool) $locale->is_default;
            }
        } catch (\Exception $e) {
            $this->localeCache = [];
        }

        return $this->localeCache;
    }

    /**
     * The locale whose values live in the base table columns.
     *
     * @return string|null
     */
    protected function getDefaultMailLocale()
    {
        foreach ($this->getMailLocales() as $code => $isDefault) {
            if ($isDefault) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Locale codes that are stored as translations rather than base columns.
     *
     * @return array
     */
    protected function getNonDefaultMailLocales()
    {
        return array_keys(array_filter(
            $this->getMailLocales(),
            function ($isDefault) {
                return !$isDefault;
            }
        ));
    }

    /**
     * Whether this model actually carries translations.
     *
     * @param mixed $model
     * @return bool
     */
    protected function isMailModelTranslatable($model)
    {
        return $model->methodExists('getTranslateAttributes')
            && $model->methodExists('setAttributeTranslated')
            && $model->methodExists('translateContext')
            && !empty($this->getNonDefaultMailLocales());
    }

    /**
     * Directory holding one locale's files, e.g. mail_templates/templates/locales/en.
     *
     * A subdirectory rather than a filename suffix: getCodeFromFilename() splits codes
     * on dots, so "…activate.en.htm" could not be told apart from a template path
     * segment. A directory also keeps File::files() -- which is not recursive -- from
     * picking locale files up in the default-locale loops.
     *
     * @param string $section templates|layouts|partials
     * @param string $locale
     * @return string
     */
    protected function localeDirectory($section, $locale)
    {
        return $this->basePath . '/' . $section . '/locales/' . $locale;
    }

    /**
     * Drop the translation cache for one model/locale pair.
     *
     * syncTranslatableAttributes() only forgets cache keys for ENABLED locales, so a
     * locale that is authored but not yet switched on would keep serving a stale (or
     * empty) cached payload for an hour after import. Forget it explicitly.
     *
     * @param mixed $model
     * @param string $locale
     * @return void
     */
    protected function forgetTranslationCache($model, $locale)
    {
        Cache::forget(sprintf(
            'translation:%s:%s:%s',
            $model->getMorphClass(),
            $model->getKey(),
            $locale
        ));
    }
}
