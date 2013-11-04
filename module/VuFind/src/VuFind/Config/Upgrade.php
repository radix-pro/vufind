<?php
/**
 * VF Configuration Upgrade Tool
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Config
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace VuFind\Config;
use VuFind\Config\Writer as ConfigWriter,
    VuFind\Exception\FileAccess as FileAccessException;

/**
 * Class to upgrade previous VuFind configurations to the current version
 *
 * @category VuFind2
 * @package  Config
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class Upgrade
{
    /**
     * Version we're upgrading from
     *
     * @var string
     */
    protected $from;

    /**
     * Version we're upgrading to
     *
     * @var string
     */
    protected $to;

    /**
     * Directory containing configurations to upgrade
     *
     * @var string
     */
    protected $oldDir;

    /**
     * Directory containing unmodified new configurations
     *
     * @var string
     */
    protected $rawDir;

    /**
     * Directory where new configurations should be written (null for test mode)
     *
     * @var string
     */
    protected $newDir;

    /**
     * Parsed old configurations
     *
     * @var array
     */
    protected $oldConfigs = array();

    /**
     * Processed new configurations
     *
     * @var array
     */
    protected $newConfigs = array();

    /**
     * Comments parsed from configuration files
     *
     * @var array
     */
    protected $comments = array();

    /**
     * Warnings generated during upgrade process
     *
     * @var array
     */
    protected $warnings = array();

    /**
     * Are we upgrading files in place rather than creating them?
     *
     * @var bool
     */
    protected $inPlaceUpgrade;

    /**
     * Constructor
     *
     * @param string $from   Version we're upgrading from.
     * @param string $to     Version we're upgrading to.
     * @param string $oldDir Directory containing old configurations.
     * @param string $rawDir Directory containing raw new configurations.
     * @param string $newDir Directory to write updated new configurations into
     * (leave null to disable writes -- used in test mode).
     */
    public function __construct($from, $to, $oldDir, $rawDir, $newDir = null)
    {
        $this->from = $from;
        $this->to = $to;
        $this->oldDir = $oldDir;
        $this->rawDir = $rawDir;
        $this->newDir = $newDir;
        $this->inPlaceUpgrade = ($this->oldDir == $this->newDir);
    }

    /**
     * Run through all of the necessary upgrading.
     *
     * @return void
     */
    public function run()
    {
        // Load all old configurations:
        $this->loadConfigs();

        // Upgrade them one by one and write the results to disk; order is
        // important since in some cases, settings may migrate out of config.ini
        // and into other files.
        $this->upgradeConfig();
        $this->upgradeAuthority();
        $this->upgradeFacets();
        $this->upgradeFulltext();
        $this->upgradeReserves();
        $this->upgradeSearches();
        $this->upgradeSitemap();
        $this->upgradeSms();
        $this->upgradeSummon();
        $this->upgradeWorldCat();

        // The following routines load special configurations that were not
        // explicitly loaded by loadConfigs:
        if ($this->from < 2) {  // some pieces only apply to 1.x upgrade!
            $this->upgradeSolrMarc();
            $this->upgradeSearchSpecs();
        }
        $this->upgradeILS();
    }

    /**
     * Get processed configurations (used by test routines).
     *
     * @return array
     */
    public function getNewConfigs()
    {
        return $this->newConfigs;
    }

    /**
     * Get warning strings generated during upgrade process.
     *
     * @return array
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * Add a warning message.
     *
     * @param string $msg Warning message.
     *
     * @return void
     */
    protected function addWarning($msg)
    {
        $this->warnings[] = $msg;
    }

    /**
     * Support function -- merge the contents of two arrays parsed from ini files.
     *
     * @param string $config_ini The base config array.
     * @param string $custom_ini Overrides to apply on top of the base array.
     *
     * @return array             The merged results.
     */
    public static function iniMerge($config_ini, $custom_ini)
    {
        foreach ($custom_ini as $k => $v) {
            // Make a recursive call if we need to merge array values into an
            // existing key...  otherwise just drop the value in place.
            if (is_array($v) && isset($config_ini[$k])) {
                $config_ini[$k] = self::iniMerge($config_ini[$k], $custom_ini[$k]);
            } else {
                $config_ini[$k] = $v;
            }
        }
        return $config_ini;
    }

    /**
     * Load the old config.ini settings.
     *
     * @return void
     */
    protected function loadOldBaseConfig()
    {
        // Load the base settings:
        $mainArray = parse_ini_file($this->oldDir . '/config.ini', true);

        // Merge in local overrides as needed.  VuFind 2 structures configurations
        // differently, so people who used this mechanism will need to refactor
        // their configurations to take advantage of the new "local directory"
        // feature.  For now, we'll just merge everything to avoid losing settings.
        if (isset($mainArray['Extra_Config'])
            && isset($mainArray['Extra_Config']['local_overrides'])
        ) {
            $file = trim(
                $this->oldDir . '/' . $mainArray['Extra_Config']['local_overrides']
            );
            $localOverride = @parse_ini_file($file, true);
            if ($localOverride) {
                $mainArray = self::iniMerge($mainArray, $localOverride);
            }
        }

        // Save the configuration to the appropriate place:
        $this->oldConfigs['config.ini'] = $mainArray;
    }

    /**
     * Find the path to the old configuration file.
     *
     * @param string $filename Filename of configuration file.
     *
     * @return string
     */
    protected function getOldConfigPath($filename)
    {
        // Check if the user has overridden the filename in the [Extra_Config]
        // section:
        $index = str_replace('.ini', '', $filename);
        if (isset($this->oldConfigs['config.ini']['Extra_Config'][$index])) {
            $path = $this->oldDir . '/'
                . $this->oldConfigs['config.ini']['Extra_Config'][$index];
            if (file_exists($path) && is_file($path)) {
                return $path;
            }
        }
        return $this->oldDir . '/' . $filename;
    }

    /**
     * Load all of the user's existing configurations.
     *
     * @return void
     */
    protected function loadConfigs()
    {
        // Configuration files to load.  Note that config.ini must always be loaded
        // first so that getOldConfigPath can work properly!
        $configs = array(
            'config.ini', 'authority.ini', 'facets.ini', 'reserves.ini',
            'searches.ini', 'Summon.ini', 'WorldCat.ini', 'sms.ini'
        );
        foreach ($configs as $config) {
            // Special case for config.ini, since we may need to overlay extra
            // settings:
            if ($config == 'config.ini') {
                $this->loadOldBaseConfig();
            } else {
                $path = $this->getOldConfigPath($config);
                $this->oldConfigs[$config] = file_exists($path)
                    ? parse_ini_file($path, true) : array();
            }
            $this->newConfigs[$config]
                = parse_ini_file($this->rawDir . '/' . $config, true);
            $this->comments[$config]
                = $this->extractComments($this->rawDir . '/' . $config);
        }
    }

    /**
     * Apply settings from an old configuration to a new configuration.
     *
     * @param string $filename     Name of the configuration being updated.
     * @param array  $fullSections Array of section names that need to be fully
     * overridden (as opposed to overridden on a setting-by-setting basis).
     *
     * @return void
     */
    protected function applyOldSettings($filename, $fullSections = array())
    {
        // First override all individual settings:
        foreach ($this->oldConfigs[$filename] as $section => $subsection) {
            foreach ($subsection as $key => $value) {
                $this->newConfigs[$filename][$section][$key] = $value;
            }
        }

        // Now override on a section-by-section basis where necessary:
        foreach ($fullSections as $section) {
            $this->newConfigs[$filename][$section]
                = isset($this->oldConfigs[$filename][$section])
                ? $this->oldConfigs[$filename][$section] : array();
        }
    }

    /**
     * Save a modified configuration file.
     *
     * @param string $filename Name of config file to write (contents will be
     * pulled from current state of object properties).
     *
     * @throws FileAccessException
     * @return void
     */
    protected function saveModifiedConfig($filename)
    {
        if (null === $this->newDir) {   // skip write if no destination
            return;
        }

        // If we're doing an in-place upgrade, and the source file is empty,
        // there is no point in upgrading anything (the file doesn't exist).
        if (empty($this->oldConfigs[$filename]) && $this->inPlaceUpgrade) {
            return;
        }

        // If target file already exists, back it up:
        $outfile = $this->newDir . '/' . $filename;
        copy($outfile, $outfile . '.bak.' . time());

        $writer = new ConfigWriter(
            $outfile, $this->newConfigs[$filename], $this->comments[$filename]
        );
        if (!$writer->save()) {
            throw new FileAccessException(
                "Error: Problem writing to {$outfile}."
            );
        }
    }

    /**
     * Save an unmodified configuration file -- copy the old version, unless it is
     * the same as the new version!
     *
     * @param string $filename Path to the old config file
     *
     * @throws FileAccessException
     * @return void
     */
    protected function saveUnmodifiedConfig($filename)
    {
        if (null === $this->newDir) {   // skip write if no destination
            return;
        }

        if ($this->inPlaceUpgrade) {    // skip write if doing in-place upgrade
            return;
        }

        // Figure out directories for all versions of this config file:
        $src = $this->getOldConfigPath($filename);
        $raw = $this->rawDir . '/' . $filename;
        $dest = $this->newDir . '/' . $filename;

        // Compare the source file against the raw file; if they happen to be the
        // same, we don't need to copy anything!
        if (md5(file_get_contents($src)) == md5(file_get_contents($raw))) {
            return;
        }

        // If we got this far, we need to copy the user's file into place:
        if (!copy($src, $dest)) {
            throw new FileAccessException(
                "Error: Could not copy {$src} to {$dest}."
            );
        }
    }

    /**
     * Check for invalid theme setting.
     *
     * @param string $setting Name of setting in [Site] section to check.
     * @param string $default Default value to use if invalid option was found.
     *
     * @return void
     */
    protected function checkTheme($setting, $default)
    {
        // If a setting is not set, there is nothing to check:
        $theme = isset($this->newConfigs['config.ini']['Site'][$setting])
            ? $this->newConfigs['config.ini']['Site'][$setting] : null;
        if (empty($theme)) {
            return;
        }

        $parts = explode(',', $theme);
        $theme = trim($parts[0]);

        if (!file_exists(APPLICATION_PATH . '/themes/' . $theme)
            || !is_dir(APPLICATION_PATH . '/themes/' . $theme)
        ) {
            $this->addWarning(
                "WARNING: This version of VuFind does not support "
                . "the {$theme} theme.  Your config.ini [Site] {$setting} setting "
                . "has been reset to the default: {$default}.  You may need to "
                . "reimplement your custom theme."
            );
            $this->newConfigs['config.ini']['Site'][$setting] = $default;
        }
    }

    /**
     * Is this a default BulkExport options setting?
     *
     * @param string $eo Bulk export options
     *
     * @return bool
     */
    protected function isDefaultBulkExportOptions($eo)
    {
        return ($this->from == '1.4' && $eo == 'MARC:MARCXML:EndNote:RefWorks:BibTeX')
            || ($this->from == '1.3' && $eo == 'MARC:EndNote:RefWorks:BibTeX')
            || ($this->from == '1.2' && $eo == 'MARC:EndNote:BibTeX')
            || ($this->from == '1.1' && $eo == 'MARC:EndNote');
    }

    /**
     * Add warnings if Amazon problems were found.
     *
     * @param array $config Configuration to check
     *
     * @return void
     */
    protected function checkAmazonConfig($config)
    {
        // Warn the user if they have Amazon enabled but do not have the appropriate
        // credentials set up.
        $hasAmazonReview = isset($config['Content']['reviews'])
            && stristr($config['Content']['reviews'], 'amazon');
        $hasAmazonCover = isset($config['Content']['coverimages'])
            && stristr($config['Content']['coverimages'], 'amazon');
        if ($hasAmazonReview || $hasAmazonCover) {
            if (!isset($config['Content']['amazonsecret'])) {
                $this->addWarning(
                    'WARNING: You have Amazon content enabled but are missing '
                    . 'the required amazonsecret setting in the [Content] section '
                    . 'of config.ini'
                );
            }
            if (!isset($config['Content']['amazonassociate'])) {
                $this->addWarning(
                    'WARNING: You have Amazon content enabled but are missing '
                    . 'the required amazonassociate setting in the [Content] section'
                    . ' of config.ini'
                );
            }
        }
    }

    /**
     * Upgrade config.ini.
     *
     * @throws FileAccessException
     * @return void
     */
    protected function upgradeConfig()
    {
        // override new version's defaults with matching settings from old version:
        $this->applyOldSettings('config.ini');

        // Set up reference for convenience (and shorter lines):
        $newConfig = & $this->newConfigs['config.ini'];

        // Brazilian Portuguese language file is now disabled by default (since
        // it is very incomplete, and regular Portuguese file is now available):
        if (isset($newConfig['Languages']['pt-br'])) {
            unset($newConfig['Languages']['pt-br']);
        }

        // If the [BulkExport] options setting is an old default, update it to
        // reflect the fact that we now support more options.
        if ($this->isDefaultBulkExportOptions($newConfig['BulkExport']['options'])) {
            $newConfig['BulkExport']['options']
                = 'MARC:MARCXML:EndNote:EndNoteWeb:RefWorks:BibTeX';
        }

        // Warn the user about Amazon configuration issues:
        $this->checkAmazonConfig($newConfig);

        // Warn the user if they have enabled a deprecated Google API:
        if (isset($newConfig['GoogleSearch'])) {
            unset($newConfig['GoogleSearch']);
            $this->addWarning(
                'The [GoogleSearch] section of config.ini is no '
                . 'longer supported due to changes in Google APIs.'
            );
        }

        // Warn the user if they are using an unsupported theme:
        $this->checkTheme('theme', 'blueprint');
        $this->checkTheme('mobile_theme', 'jquerymobile');

        // Translate legacy auth settings:
        if (strtolower($newConfig['Authentication']['method']) == 'db') {
            $newConfig['Authentication']['method'] = 'Database';
        }
        if (strtolower($newConfig['Authentication']['method']) == 'sip') {
            $newConfig['Authentication']['method'] = 'SIP2';
        }

        // Translate legacy session settings:
        $newConfig['Session']['type'] = ucwords(
            str_replace('session', '', strtolower($newConfig['Session']['type']))
        );
        if ($newConfig['Session']['type'] == 'Mysql') {
            $newConfig['Session']['type'] = 'Database';
        }

        // Eliminate obsolete database settings:
        $newConfig['Database']
            = array('database' => $newConfig['Database']['database']);

        // Eliminate obsolete config override settings:
        unset($newConfig['Extra_Config']);

        // Update stats settings:
        if (isset($newConfig['Statistics']['enabled'])) {
            // If "enabled" is on, this equates to the new system being in Solr mode:
            if ($newConfig['Statistics']['enabled']) {
                $newConfig['Statistics']['mode'] = array('Solr');
            }

            // Whether or not "enabled" is on, remove the deprecated setting:
            unset($newConfig['Statistics']['enabled']);
        }

        // Update generator if it is default value:
        if (isset($newConfig['Site']['generator'])
            && $newConfig['Site']['generator'] == 'VuFind ' . $this->from
        ) {
            $newConfig['Site']['generator'] = 'VuFind ' . $this->to;
        }

        // Deal with shard settings (which may have to be moved to another file):
        $this->upgradeShardSettings();

        // save the file
        $this->saveModifiedConfig('config.ini');
    }

    /**
     * Upgrade facets.ini.
     *
     * @throws FileAccessException
     * @return void
     */
    protected function upgradeFacets()
    {
        // we want to retain the old installation's various facet groups
        // exactly as-is
        $facetGroups = array(
            'Results', 'ResultsTop', 'Advanced', 'Author', 'CheckboxFacets',
            'HomePage'
        );
        $this->applyOldSettings('facets.ini', $facetGroups);

        // fill in home page facets with advanced facets if missing:
        if (!isset($this->oldConfigs['facets.ini']['HomePage'])) {
            $this->newConfigs['facets.ini']['HomePage']
                = $this->newConfigs['facets.ini']['Advanced'];
        }

        // save the file
        $this->saveModifiedConfig('facets.ini');
    }

    /**
     * Update an old VuFind 1.x-style autocomplete handler name to the new style.
     *
     * @param string $name Name of module.
     *
     * @return string
     */
    protected function upgradeAutocompleteName($name)
    {
        if ($name == 'NoAutocomplete') {
            return 'None';
        }
        return str_replace('Autocomplete', '', $name);
    }

    /**
     * Upgrade searches.ini.
     *
     * @throws FileAccessException
     * @return void
     */
    protected function upgradeSearches()
    {
        // we want to retain the old installation's Basic/Advanced search settings
        // and sort settings exactly as-is
        $groups = array(
            'Basic_Searches', 'Advanced_Searches', 'Sorting', 'DefaultSortingByType'
        );
        $this->applyOldSettings('searches.ini', $groups);

        // Fix autocomplete settings in case they use the old style:
        $newConfig = & $this->newConfigs['searches.ini'];
        if (isset($newConfig['Autocomplete']['default_handler'])) {
            $newConfig['Autocomplete']['default_handler']
                = $this->upgradeAutocompleteName(
                    $newConfig['Autocomplete']['default_handler']
                );
        }
        if (isset($newConfig['Autocomplete_Types'])) {
            foreach ($newConfig['Autocomplete_Types'] as $k => $v) {
                $parts = explode(':', $v);
                $parts[0] = $this->upgradeAutocompleteName($parts[0]);
                $newConfig['Autocomplete_Types'][$k] = implode(':', $parts);
            }
        }

        // save the file
        $this->saveModifiedConfig('searches.ini');
    }

    /**
     * Upgrade fulltext.ini.
     *
     * @throws FileAccessException
     * @return void
     */
    protected function upgradeFulltext()
    {
        $this->saveUnmodifiedConfig('fulltext.ini');
    }

    /**
     * Upgrade sitemap.ini.
     *
     * @throws FileAccessException
     * @return void
     */
    protected function upgradeSitemap()
    {
        $this->saveUnmodifiedConfig('sitemap.ini');
    }

    /**
     * Upgrade sms.ini.
     *
     * @throws FileAccessException
     * @return void
     */
    protected function upgradeSms()
    {
        $this->applyOldSettings('sms.ini', array('Carriers'));
        $this->saveModifiedConfig('sms.ini');
    }

    /**
     * Upgrade authority.ini.
     *
     * @throws FileAccessException
     * @return void
     */
    protected function upgradeAuthority()
    {
        // we want to retain the old installation's search and facet settings
        // exactly as-is
        $groups = array(
            'Facets', 'Basic_Searches', 'Advanced_Searches', 'Sorting'
        );
        $this->applyOldSettings('authority.ini', $groups);

        // save the file
        $this->saveModifiedConfig('authority.ini');
    }

    /**
     * Upgrade reserves.ini.
     *
     * @throws FileAccessException
     * @return void
     */
    protected function upgradeReserves()
    {
        // If Reserves module is disabled, don't bother updating config:
        if (!isset($this->newConfigs['config.ini']['Reserves']['search_enabled'])
            || !$this->newConfigs['config.ini']['Reserves']['search_enabled']
        ) {
            return;
        }

        // we want to retain the old installation's search and facet settings
        // exactly as-is
        $groups = array(
            'Facets', 'Basic_Searches', 'Advanced_Searches', 'Sorting'
        );
        $this->applyOldSettings('reserves.ini', $groups);

        // save the file
        $this->saveModifiedConfig('reserves.ini');
    }

    /**
     * Upgrade Summon.ini.
     *
     * @throws FileAccessException
     * @return void
     */
    protected function upgradeSummon()
    {
        // If Summon is disabled in our current configuration, we don't need to
        // load any Summon-specific settings:
        if (!isset($this->newConfigs['config.ini']['Summon']['apiKey'])) {
            return;
        }

        // we want to retain the old installation's search and facet settings
        // exactly as-is
        $groups = array(
            'Facets', 'FacetsTop', 'Basic_Searches', 'Advanced_Searches', 'Sorting'
        );
        $this->applyOldSettings('Summon.ini', $groups);

        // save the file
        $this->saveModifiedConfig('Summon.ini');
    }

    /**
     * Upgrade WorldCat.ini.
     *
     * @throws FileAccessException
     * @return void
     */
    protected function upgradeWorldCat()
    {
        // If WorldCat is disabled in our current configuration, we don't need to
        // load any WorldCat-specific settings:
        if (!isset($this->newConfigs['config.ini']['WorldCat']['apiKey'])) {
            return;
        }

        // we want to retain the old installation's search settings exactly as-is
        $groups = array(
            'Basic_Searches', 'Advanced_Searches', 'Sorting'
        );
        $this->applyOldSettings('WorldCat.ini', $groups);

        // save the file
        $this->saveModifiedConfig('WorldCat.ini');
    }

    /**
     * Upgrade SolrMarc configurations.
     *
     * @throws FileAccessException
     * @return void
     */
    protected function upgradeSolrMarc()
    {
        if (null === $this->newDir) {   // skip this step if no write destination
            return;
        }

        // Is there a marc_local.properties file?
        $src = realpath($this->oldDir . '/../../import/marc_local.properties');
        if (empty($src) || !file_exists($src)) {
            return;
        }

        // Does the file contain any meaningful lines?
        $lines = file($src);
        $empty = true;
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && substr($line, 0, 1) != '#') {
                $empty = false;
                break;
            }
        }

        // Copy the file if it contains customizations:
        if (!$empty) {
            $dest = realpath($this->newDir . '/../../import')
                . '/marc_local.properties';
            if (!copy($src, $dest) || !file_exists($dest)) {
                throw new FileAccessException(
                    "Cannot copy {$src} to {$dest}."
                );
            }
        }
    }

    /**
     * Upgrade .yaml configurations.
     *
     * @throws FileAccessException
     * @return void
     */
    protected function upgradeSearchSpecs()
    {
        if (null === $this->newDir) {   // skip this step if no write destination
            return;
        }

        // VuFind 1.x uses *_local.yaml files as overrides; VuFind 2.x uses files
        // with the same filename in the local directory.  Copy any old override
        // files into the new expected location:
        $files = array('searchspecs', 'authsearchspecs', 'reservessearchspecs');
        foreach ($files as $file) {
            $old = $this->oldDir . '/' . $file . '_local.yaml';
            $new = $this->newDir . '/' . $file . '.yaml';
            if (file_exists($old)) {
                if (!copy($old, $new)) {
                    throw new FileAccessException(
                        "Cannot copy {$old} to {$new}."
                    );
                }
            }
        }
    }

    /**
     * Upgrade ILS driver configuration.
     *
     * @throws FileAccessException
     * @return void
     */
    protected function upgradeILS()
    {
        $driver = isset($this->newConfigs['config.ini']['Catalog']['driver'])
            ? $this->newConfigs['config.ini']['Catalog']['driver'] : '';
        if (empty($driver)) {
            $this->addWarning("WARNING: Could not find ILS driver setting.");
        } else if ('Sample' == $driver) {
            // No configuration file for Sample driver
        } else if (!file_exists($this->oldDir . '/' . $driver . '.ini')) {
            $this->addWarning(
                "WARNING: Could not find {$driver}.ini file; "
                . "check your ILS driver configuration."
            );
        } else {
            $this->saveUnmodifiedConfig($driver . '.ini');
        }

        // If we're set to load NoILS.ini on failure, copy that over as well:
        if (isset($this->newConfigs['config.ini']['Catalog']['loadNoILSOnFailure'])
            && $this->newConfigs['config.ini']['Catalog']['loadNoILSOnFailure']
        ) {
            // If NoILS is also the main driver, we don't need to copy it twice:
            if ($driver != 'NoILS') {
                $this->saveUnmodifiedConfig('NoILS.ini');
            }
        }
    }

    /**
     * Upgrade shard settings (they have moved to a different config file, so
     * this is handled as a separate method so that all affected settings are
     * addressed in one place.
     *
     * This gets called from updateConfig(), which gets called before other
     * configuration upgrade routines.  This means that we need to modify the
     * config.ini settings in the newConfigs property (since it is currently
     * being worked on and will be written to disk shortly), but we need to
     * modify the searches.ini/facets.ini settings in the oldConfigs property
     * (because they have not been processed yet).
     *
     * @return void
     */
    protected function upgradeShardSettings()
    {
        // move settings from config.ini to searches.ini:
        if (isset($this->newConfigs['config.ini']['IndexShards'])) {
            $this->oldConfigs['searches.ini']['IndexShards']
                = $this->newConfigs['config.ini']['IndexShards'];
            unset($this->newConfigs['config.ini']['IndexShards']);
        }
        if (isset($this->newConfigs['config.ini']['ShardPreferences'])) {
            $this->oldConfigs['searches.ini']['ShardPreferences']
                = $this->newConfigs['config.ini']['ShardPreferences'];
            unset($this->newConfigs['config.ini']['ShardPreferences']);
        }

        // move settings from facets.ini to searches.ini (merging StripFacets
        // setting with StripFields setting):
        if (isset($this->oldConfigs['facets.ini']['StripFacets'])) {
            if (!isset($this->oldConfigs['searches.ini']['StripFields'])) {
                $this->oldConfigs['searches.ini']['StripFields'] = array();
            }
            foreach ($this->oldConfigs['facets.ini']['StripFacets'] as $k => $v) {
                // If we already have values for the current key, merge and dedupe:
                if (isset($this->oldConfigs['searches.ini']['StripFields'][$k])) {
                    $v .= ',' . $this->oldConfigs['searches.ini']['StripFields'][$k];
                    $parts = explode(',', $v);
                    foreach ($parts as $i => $part) {
                        $parts[$i] = trim($part);
                    }
                    $v = implode(',', array_unique($parts));
                }
                $this->oldConfigs['searches.ini']['StripFields'][$k] = $v;
            }
            unset($this->oldConfigs['facets.ini']['StripFacets']);
        }
    }

    /**
     * Read the specified file and return an associative array of this format
     * containing all comments extracted from the file:
     *
     * array =>
     *   'sections' => array
     *     'section_name_1' => array
     *       'before' => string ("Comments found at the beginning of this section")
     *       'inline' => string ("Comments found at the end of the section's line")
     *       'settings' => array
     *         'setting_name_1' => array
     *           'before' => string ("Comments found before this setting")
     *           'inline' => string ("Comments found at the end of setting's line")
     *           ...
     *         'setting_name_n' => array (same keys as setting_name_1)
     *        ...
     *      'section_name_n' => array (same keys as section_name_1)
     *   'after' => string ("Comments found at the very end of the file")
     *
     * @param string $filename Name of ini file to read.
     *
     * @return array           Associative array as described above.
     */
    protected function extractComments($filename)
    {
        $lines = file($filename);

        // Initialize our return value:
        $retVal = array('sections' => array(), 'after' => '');

        // Initialize variables for tracking status during parsing:
        $section = $comments = '';

        foreach ($lines as $line) {
            // To avoid redundant processing, create a trimmed version of the current
            // line:
            $trimmed = trim($line);

            // Is the current line a comment?  If so, add to the currentComments
            // string. Note that we treat blank lines as comments.
            if (substr($trimmed, 0, 1) == ';' || empty($trimmed)) {
                $comments .= $line;
            } else if (substr($trimmed, 0, 1) == '['
                && ($closeBracket = strpos($trimmed, ']')) > 1
            ) {
                // Is the current line the start of a section?  If so, create the
                // appropriate section of the return value:
                $section = substr($trimmed, 1, $closeBracket - 1);
                if (!empty($section)) {
                    // Grab comments at the end of the line, if any:
                    if (($semicolon = strpos($trimmed, ';')) !== false) {
                        $inline = trim(substr($trimmed, $semicolon));
                    } else {
                        $inline = '';
                    }
                    $retVal['sections'][$section] = array(
                        'before' => $comments,
                        'inline' => $inline,
                        'settings' => array());
                    $comments = '';
                }
            } else if (($equals = strpos($trimmed, '=')) !== false) {
                // Is the current line a setting?  If so, add to the return value:
                $set = trim(substr($trimmed, 0, $equals));
                $set = trim(str_replace('[]', '', $set));
                if (!empty($section) && !empty($set)) {
                    // Grab comments at the end of the line, if any:
                    if (($semicolon = strpos($trimmed, ';')) !== false) {
                        $inline = trim(substr($trimmed, $semicolon));
                    } else {
                        $inline = '';
                    }
                    // Currently, this data structure doesn't support arrays very
                    // well, since it can't distinguish which line of the array
                    // corresponds with which comments.  For now, we just append all
                    // the preceding and inline comments together for arrays.  Since
                    // we rarely use arrays in the config.ini file, this isn't a big
                    // concern, but we should improve it if we ever need to.
                    if (!isset($retVal['sections'][$section]['settings'][$set])) {
                        $retVal['sections'][$section]['settings'][$set]
                            = array('before' => $comments, 'inline' => $inline);
                    } else {
                        $retVal['sections'][$section]['settings'][$set]['before']
                            .= $comments;
                        $retVal['sections'][$section]['settings'][$set]['inline']
                            .= "\n" . $inline;
                    }
                    $comments = '';
                }
            }
        }

        // Store any leftover comments following the last setting:
        $retVal['after'] = $comments;

        return $retVal;
    }
}