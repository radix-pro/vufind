<?php
/**
 * Model for MARC records in Solr.
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
 * @package  RecordDrivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
namespace VuFind\RecordDriver;
use VuFind\Exception\ILS as ILSException,
    VuFind\View\Helper\Root\RecordLink,
    VuFind\XSLT\Processor as XSLTProcessor;

/**
 * Model for MARC records in Solr.
 *
 * @category VuFind2
 * @package  RecordDrivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
class SolrMarc extends SolrDefault
{
    /**
     * MARC record
     *
     * @var \File_MARC_Record
     */
    protected $marcRecord;

    /**
     * ILS connection
     *
     * @var \VuFind\ILS\Connection
     */
    protected $ils = null;

    /**
     * Hold logic
     *
     * @var \VuFind\ILS\Logic\Holds
     */
    protected $holdLogic;

    /**
     * Title hold logic
     *
     * @var \VuFind\ILS\Logic\TitleHolds
     */
    protected $titleHoldLogic;

    /**
     * Set raw data to initialize the object.
     *
     * @param mixed $data Raw data representing the record; Record Model
     * objects are normally constructed by Record Driver objects using data
     * passed in from a Search Results object.  In this case, $data is a Solr record
     * array containing MARC data in the 'fullrecord' field.
     *
     * @return void
     */
    public function setRawData($data)
    {
        // Call the parent's set method...
        parent::setRawData($data);

        // Also process the MARC record:
        $marc = trim($data['fullrecord']);

        // check if we are dealing with MARCXML
        $xmlHead = '<?xml version';
        if (strcasecmp(substr($marc, 0, strlen($xmlHead)), $xmlHead) === 0) {
            $marc = new \File_MARCXML($marc, \File_MARCXML::SOURCE_STRING);
        } else {
            // When indexing over HTTP, SolrMarc may use entities instead of certain
            // control characters; we should normalize these:
            $marc = str_replace(
                array('#29;', '#30;', '#31;'), array("\x1D", "\x1E", "\x1F"), $marc
            );
            $marc = new \File_MARC($marc, \File_MARC::SOURCE_STRING);
        }

        $this->marcRecord = $marc->next();
        if (!$this->marcRecord) {
            throw new \File_MARC_Exception('Cannot Process MARC Record');
        }
    }

    /**
     * Get access restriction notes for the record.
     *
     * @return array
     */
    public function getAccessRestrictions()
    {
        return $this->getFieldArray('506');
    }

    /**
     * Get all subject headings associated with this record.  Each heading is
     * returned as an array of chunks, increasing from least specific to most
     * specific.
     *
     * @return array
     */
    public function getAllSubjectHeadings()
    {
        // These are the fields that may contain subject headings:
        if ($this->is_Rusmarc() == true):   /** Uj **/
            $fields = array(
                '600', '601', '605', '606', '610'
            );
        else:
            $fields = array(
                '600', '610', '611', '630', '648', '650', '651', '653', '655', '656'
            );
        endif;

        // This is all the collected data:
        $retval = array();

        // Try each MARC field one at a time:
        foreach ($fields as $field) {
            // Do we have any results for the current field?  If not, try the next.
            $results = $this->marcRecord->getFields($field);
            if (!$results) {
                continue;
            }

            // If we got here, we found results -- let's loop through them.
            foreach ($results as $result) {
                // Start an array for holding the chunks of the current heading:
                $current = array();

                // Get all the chunks and collect them together:
                $subfields = $result->getSubfields();
                if ($subfields) {
                    foreach ($subfields as $subfield) {
                        // Numeric subfields are for control purposes and should not
                        // be displayed:
                        if (!is_numeric($subfield->getCode())) {
                            $current[] = $subfield->getData();
                        }
                    }
                    // If we found at least one chunk, add a heading to our result:
                    if (!empty($current)) {
                        $retval[] = $current;
                    }
                }
            }
        }

        // Send back everything we collected:
        return $retval;
    }

    /**
     * Get award notes for the record.
     *
     * @return array
     */
    public function getAwards()
    {
        return $this->getFieldArray('586');
    }

    /**
     * Get the bibliographic level of the current record.
     *
     * @return string
     */
    public function getBibliographicLevel()
    {
        $leader = $this->marcRecord->getLeader();
        $biblioLevel = strtoupper($leader[7]);

        switch ($biblioLevel) {
        case 'M': // Monograph
            return "Monograph";
        case 'S': // Serial
            return "Serial";
        case 'A': // Monograph Part
            return "MonographPart";
        case 'B': // Serial Part
            return "SerialPart";
        case 'C': // Collection
            return "Collection";
        case 'D': // Collection Part
            return "CollectionPart";
        default:
            return "Unknown";
        }
    }

    /**
     * Get notes on bibliography content.
     *
     * @return array
     */
    public function getBibliographyNotes()
    {
        return $this->getFieldArray('504');
    }

    /**
     * Get the main corporate author (if any) for the record.
     *
     * @return string
     */
    public function getCorporateAuthor()
    {
        // Try 110 first -- if none found, try 710 next.
        $main = $this->getFirstFieldValue('110', array('a', 'b'));
        if (!empty($main)) {
            return $main;
        }
        return $this->getFirstFieldValue('710', array('a', 'b'));
    }

    /**
     * Return an array of all values extracted from the specified field/subfield
     * combination.  If multiple subfields are specified and $concat is true, they
     * will be concatenated together in the order listed -- each entry in the array
     * will correspond with a single MARC field.  If $concat is false, the return
     * array will contain separate entries for separate subfields.
     *
     * @param string $field     The MARC field number to read
     * @param array  $subfields The MARC subfield codes to read
     * @param bool   $concat    Should we concatenate subfields?
     *
     * @return array
     */
    protected function getFieldArray($field, $subfields = null, $concat = true)
    {
        // Default to subfield a if nothing is specified.
        if (!is_array($subfields)) {
            $subfields = array('a');
        }

        // Initialize return array
        $matches = array();

        // Try to look up the specified field, return empty array if it doesn't
        // exist.
        $fields = $this->marcRecord->getFields($field);
        if (!is_array($fields)) {
            return $matches;
        }

        // Extract all the requested subfields, if applicable.
        foreach ($fields as $currentField) {
            $next = $this->getSubfieldArray($currentField, $subfields, $concat);
            $matches = array_merge($matches, $next);
        }

        return $matches;
    }

    /**
     * Get notes on finding aids related to the record.
     *
     * @return array
     */
    public function getFindingAids()
    {
        return $this->getFieldArray('555');
    }

    /**
     * Get the first value matching the specified MARC field and subfields.
     * If multiple subfields are specified, they will be concatenated together.
     *
     * @param string $field     The MARC field to read
     * @param array  $subfields The MARC subfield codes to read
     *
     * @return string
     */
    protected function getFirstFieldValue($field, $subfields = null)
    {
        $matches = $this->getFieldArray($field, $subfields);
        return (is_array($matches) && count($matches) > 0) ?
            $matches[0] : null;
    }

    /**
     * Get general notes on the record.
     *
     * @return array
     */
    public function getGeneralNotes()
    {
        //return $this->getFieldArray('500');

        /** Uj **/
        if ($this->is_Rusmarc() == true):
            return $this->getFieldArray('300');
        else:
            return $this->getFieldArray('500');
        endif;
    }

    /**
     * Get human readable publication dates for display purposes (may not be suitable
     * for computer processing -- use getPublicationDates() for that).
     *
     * @return array
     */
    public function getHumanReadablePublicationDates()
    {
        return $this->getPublicationInfo('c');
    }

    /**
     * Get an array of newer titles for the record.
     *
     * @return array
     */
    public function getNewerTitles()
    {
        // If the MARC links are being used, return blank array
        $fieldsNames = isset($this->mainConfig->Record->marc_links)
            ? array_map('trim', explode(',', $this->mainConfig->Record->marc_links))
            : array();
        return in_array('785', $fieldsNames) ? array() : parent::getNewerTitles();
    }

    /**
     * Get the item's publication information
     *
     * @param string $subfield The subfield to retrieve ('a' = location, 'c' = date)
     *
     * @return array
     */
    protected function getPublicationInfo($subfield = 'a')
    {
        // First check old-style 260 field:
        $results = $this->getFieldArray('260', array($subfield));

        // Now track down relevant RDA-style 264 fields; we only care about
        // copyright and publication places (and ignore copyright places if
        // publication places are present).  This behavior is designed to be
        // consistent with default SolrMarc handling of names/dates.
        $pubResults = $copyResults = array();

        $fields = $this->marcRecord->getFields('264');
        if (is_array($fields)) {
            foreach ($fields as $currentField) {
                $currentVal = $currentField->getSubfield($subfield);
                $currentVal = is_object($currentVal)
                    ? $currentVal->getData() : null;
                if (!empty($currentVal)) {
                    switch ($currentField->getIndicator('2')) {
                    case '1':
                        $pubResults[] = $currentVal;
                        break;
                    case '4':
                        $copyResults[] = $currentVal;
                        break;
                    }
                }
            }
        }
        if (count($pubResults) > 0) {
            $results = array_merge($results, $pubResults);
        } else if (count($copyResults) > 0) {
            $results = array_merge($results, $copyResults);
        }

        return $results;
    }

    /**
     * Get the item's places of publication.
     *
     * @return array
     */
    public function getPlacesOfPublication()
    {
        return $this->getPublicationInfo();
    }

    /**
     * Get an array of playing times for the record (if applicable).
     *
     * @return array
     */
    public function getPlayingTimes()
    {
        $times = $this->getFieldArray('306', array('a'), false);

        // Format the times to include colons ("HH:MM:SS" format).
        for ($x = 0; $x < count($times); $x++) {
            $times[$x] = substr($times[$x], 0, 2) . ':' .
                substr($times[$x], 2, 2) . ':' .
                substr($times[$x], 4, 2);
        }

        return $times;
    }

    /**
     * Get an array of previous titles for the record.
     *
     * @return array
     */
    public function getPreviousTitles()
    {
        // If the MARC links are being used, return blank array
        $fieldsNames = isset($this->mainConfig->Record->marc_links)
            ? array_map('trim', explode(',', $this->mainConfig->Record->marc_links))
            : array();
        return in_array('780', $fieldsNames) ? array() : parent::getPreviousTitles();
    }

    /**
     * Get credits of people involved in production of the item.
     *
     * @return array
     */
    public function getProductionCredits()
    {
        return $this->getFieldArray('508');
    }

    /**
     * Get an array of publication frequency information.
     *
     * @return array
     */
    public function getPublicationFrequency()
    {
        return $this->getFieldArray('310', array('a', 'b'));
    }

    /**
     * Get an array of strings describing relationships to other items.
     *
     * @return array
     */
    public function getRelationshipNotes()
    {
        return $this->getFieldArray('580');
    }

    /**
     * Get an array of all series names containing the record.  Array entries may
     * be either the name string, or an associative array with 'name' and 'number'
     * keys.
     *
     * @return array
     */
    public function getSeries()
    {
        $matches = array();

        // First check the 440, 800 and 830 fields for series information:
        $primaryFields = array(
            '440' => array('a', 'p'),
            '800' => array('a', 'b', 'c', 'd', 'f', 'p', 'q', 't'),
            '830' => array('a', 'p'));
        $matches = $this->getSeriesFromMARC($primaryFields);
        if (!empty($matches)) {
            return $matches;
        }

        // Now check 490 and display it only if 440/800/830 were empty:
        $secondaryFields = array('490' => array('a'));
        $matches = $this->getSeriesFromMARC($secondaryFields);
        if (!empty($matches)) {
            return $matches;
        }

        // Still no results found?  Resort to the Solr-based method just in case!
        return parent::getSeries();
    }

    /**
     * Support method for getSeries() -- given a field specification, look for
     * series information in the MARC record.
     *
     * @param array $fieldInfo Associative array of field => subfield information
     * (used to find series name)
     *
     * @return array
     */
    protected function getSeriesFromMARC($fieldInfo)
    {
        $matches = array();

        // Loop through the field specification....
        foreach ($fieldInfo as $field => $subfields) {
            // Did we find any matching fields?
            $series = $this->marcRecord->getFields($field);
            if (is_array($series)) {
                foreach ($series as $currentField) {
                    // Can we find a name using the specified subfield list?
                    $name = $this->getSubfieldArray($currentField, $subfields);
                    if (isset($name[0])) {
                        $currentArray = array('name' => $name[0]);

                        // Can we find a number in subfield v?  (Note that number is
                        // always in subfield v regardless of whether we are dealing
                        // with 440, 490, 800 or 830 -- hence the hard-coded array
                        // rather than another parameter in $fieldInfo).
                        $number
                            = $this->getSubfieldArray($currentField, array('v'));
                        if (isset($number[0])) {
                            $currentArray['number'] = $number[0];
                        }

                        // Save the current match:
                        $matches[] = $currentArray;
                    }
                }
            }
        }

        return $matches;
    }

    /**
     * Return an array of non-empty subfield values found in the provided MARC
     * field.  If $concat is true, the array will contain either zero or one
     * entries (empty array if no subfields found, subfield values concatenated
     * together in specified order if found).  If concat is false, the array
     * will contain a separate entry for each subfield value found.
     *
     * @param object $currentField Result from File_MARC::getFields.
     * @param array  $subfields    The MARC subfield codes to read
     * @param bool   $concat       Should we concatenate subfields?
     *
     * @return array
     */
    protected function getSubfieldArray($currentField, $subfields, $concat = true)
    {
        // Start building a line of text for the current field
        $matches = array();
        $currentLine = '';

        // Loop through all subfields, collecting results that match the whitelist;
        // note that it is important to retain the original MARC order here!
        $allSubfields = $currentField->getSubfields();
        if (count($allSubfields) > 0) {
            foreach ($allSubfields as $currentSubfield) {
                if (in_array($currentSubfield->getCode(), $subfields)) {
                    // Grab the current subfield value and act on it if it is
                    // non-empty:
                    $data = trim($currentSubfield->getData());
                    if (!empty($data)) {
                        // Are we concatenating fields or storing them separately?
                        if ($concat) {
                            $currentLine .= $data . ' ';
                        } else {
                            $matches[] = $data;
                        }
                    }
                }
            }
        }

        // If we're in concat mode and found data, it will be in $currentLine and
        // must be moved into the matches array.  If we're not in concat mode,
        // $currentLine will always be empty and this code will be ignored.
        if (!empty($currentLine)) {
            $matches[] = trim($currentLine);
        }

        // Send back our result array:
        return $matches;
    }

    /**
     * Get an array of summary strings for the record.
     *
     * @return array
     */
    public function getSummary()
    {
        //return $this->getFieldArray('520');

        /** Uj **/
        if ($this->is_Rusmarc() == true):
            return $this->getFieldArray('330');
        else:
            return $this->getFieldArray('520');
        endif;
    }

    /**
     * Get an array of technical details on the item represented by the record.
     *
     * @return array
     */
    public function getSystemDetails()
    {
        return $this->getFieldArray('538');
    }

    /**
     * Get an array of note about the record's target audience.
     *
     * @return array
     */
    public function getTargetAudienceNotes()
    {
        //return $this->getFieldArray('521');

        /** Uj **/
        if ($this->is_Rusmarc() == true):
            return $this->getFieldArray('333');
        else:
            return $this->getFieldArray('521');
        endif;
    }

    /**
     * Get the text of the part/section portion of the title.
     *
     * @return string
     */
    public function getTitleSection()
    {
        return $this->getFirstFieldValue('245', array('n', 'p'));
    }

    /**
     * Get the statement of responsibility that goes with the title (i.e. "by John
     * Smith").
     *
     * @return string
     */
    public function getTitleStatement()
    {
        return $this->getFirstFieldValue('245', array('c'));
    }

    /**
     * Get an array of lines from the table of contents.
     *
     * @return array
     */
    public function getTOC()
    {
        // Return empty array if we have no table of contents:
        $fields = $this->marcRecord->getFields('505');
        if (!$fields) {
            return array();
        }

        // If we got this far, we have a table -- collect it as a string:
        $toc = array();
        foreach ($fields as $field) {
            $subfields = $field->getSubfields();
            foreach ($subfields as $subfield) {
                // Break the string into appropriate chunks,  and merge them into
                // return array:
                $toc = array_merge($toc, explode('--', $subfield->getData()));
            }
        }
        return $toc;
    }

    /**
     * Get hierarchical place names (MARC field 752)
     *
     * returns an array of formatted hierarchical place names, consisting of all
     * alpha-subfields, concatenated for display
     *
     * @return array
     */
    public function getHierarchicalPlaceNames()
    {
        $placeNames = array();
        if ($fields = $this->marcRecord->getFields('752')) {
            foreach ($fields as $field) {
                $subfields = $field->getSubfields();
                $current = array();
                foreach ($subfields as $subfield) {
                    if (!is_numeric($subfield->getCode())) {
                        $current[] = $subfield->getData();
                    }
                }
                $placeNames[] = implode(' -- ', $current);
            }
        }
        return $placeNames;
    }

    /**
     * Return an array of associative URL arrays with one or more of the following
     * keys:
     *
     * <li>
     *   <ul>desc: URL description text to display (optional)</ul>
     *   <ul>url: fully-formed URL (required if 'route' is absent)</ul>
     *   <ul>route: VuFind route to build URL with (required if 'url' is absent)</ul>
     *   <ul>routeParams: Parameters for route (optional)</ul>
     *   <ul>queryString: Query params to append after building route (optional)</ul>
     * </li>
     *
     * @return array
     */
    public function getURLs()
    {
        $retVal = array();

        // Which fields/subfields should we check for URLs?
        $fieldsToCheck = array(
            '856' => array('y', 'z'),   // Standard URL
            '555' => array('a')         // Cumulative index/finding aids
        );

        foreach ($fieldsToCheck as $field => $subfields) {
            $urls = $this->marcRecord->getFields($field);
            if ($urls) {
                foreach ($urls as $url) {
                    // Is there an address in the current field?
                    $address = $url->getSubfield('u');
                    if ($address) {
                        $address = $address->getData();

                        // Is there a description?  If not, just use the URL itself.
                        foreach ($subfields as $current) {
                            $desc = $url->getSubfield($current);
                            if ($desc) {
                                break;
                            }
                        }
                        if ($desc) {
                            $desc = $desc->getData();
                        } else {
                            $desc = $address;
                        }

                        $retVal[] = array('url' => $address, 'desc' => $desc);
                    }
                }
            }
        }

        return $retVal;
    }

    /**
     * Get all record links related to the current record. Each link is returned as
     * array.
     * Format:
     * array(
     *        array(
     *               'title' => label_for_title
     *               'value' => link_name
     *               'link'  => link_URI
     *        ),
     *        ...
     * )
     *
     * @return null|array
     */
    public function getAllRecordLinks()
    {
        // Load configurations:
        $fieldsNames = isset($this->mainConfig->Record->marc_links)
            ? explode(',', $this->mainConfig->Record->marc_links) : array();
        $useVisibilityIndicator
            = isset($this->mainConfig->Record->marc_links_use_visibility_indicator)
            ? $this->mainConfig->Record->marc_links_use_visibility_indicator : true;

        $retVal = array();
        foreach ($fieldsNames as $value) {
            $value = trim($value);
            $fields = $this->marcRecord->getFields($value);
            if (!empty($fields)) {
                foreach ($fields as $field) {
                    // Check to see if we should display at all
                    if ($useVisibilityIndicator) {
                        $visibilityIndicator = $field->getIndicator('1');
                        if ($visibilityIndicator == '1') {
                            continue;
                        }
                    }

                    // Get data for field
                    $tmp = $this->getFieldData($field);
                    if (is_array($tmp)) {
                        $retVal[] = $tmp;
                    }
                }
            }
        }
        return empty($retVal) ? null : $retVal;
    }

    /**
     * Support method for getFieldData() -- factor the relationship indicator
     * into the field number where relevant to generate a note to associate
     * with a record link.
     *
     * @param File_MARC_Data_Field $field Field to examine
     *
     * @return string
     */
    protected function getRecordLinkNote($field)
    {
        // Normalize blank relationship indicator to 0:
        $relationshipIndicator = $field->getIndicator('2');
        if ($relationshipIndicator == ' ') {
            $relationshipIndicator = '0';
        }

        // Assign notes based on the relationship type
        $value = $field->getTag();
        switch ($value) {
        case '780':
            if (in_array($relationshipIndicator, range('0', '7'))) {
                $value .= '_' . $relationshipIndicator;
            }
            break;
        case '785':
            if (in_array($relationshipIndicator, range('0', '8'))) {
                $value .= '_' . $relationshipIndicator;
            }
            break;
        }

        return 'note_' . $value;
    }

    /**
     * Returns the array element for the 'getAllRecordLinks' method
     *
     * @param File_MARC_Data_Field $field Field to examine
     *
     * @return array|bool                 Array on success, boolean false if no
     * valid link could be found in the data.
     */
    protected function getFieldData($field)
    {
        // Make sure that there is a t field to be displayed:
        if ($title = $field->getSubfield('t')) {
            $title = $title->getData();
        } else {
            return false;
        }

        $linkTypeSetting = isset($this->mainConfig->Record->marc_links_link_types)
            ? $this->mainConfig->Record->marc_links_link_types
            : 'id,oclc,dlc,isbn,issn,title';
        $linkTypes = explode(',', $linkTypeSetting);
        $linkFields = $field->getSubfields('w');

        // Run through the link types specified in the config.
        // For each type, check field for reference
        // If reference found, exit loop and go straight to end
        // If no reference found, check the next link type instead
        foreach ($linkTypes as $linkType) {
            switch (trim($linkType)){
            case 'oclc':
                foreach ($linkFields as $current) {
                    if ($oclc = $this->getIdFromLinkingField($current, 'OCoLC')) {
                        $link = array('type' => 'oclc', 'value' => $oclc);
                    }
                }
                break;
            case 'dlc':
                foreach ($linkFields as $current) {
                    if ($dlc = $this->getIdFromLinkingField($current, 'DLC', true)) {
                        $link = array('type' => 'dlc', 'value' => $dlc);
                    }
                }
                break;
            case 'id':
                foreach ($linkFields as $current) {
                    if ($bibLink = $this->getIdFromLinkingField($current)) {
                        $link = array('type' => 'bib', 'value' => $bibLink);
                    }
                }
                break;
            case 'isbn':
                if ($isbn = $field->getSubfield('z')) {
                    $link = array(
                        'type' => 'isn', 'value' => trim($isbn->getData()),
                        'exclude' => $this->getUniqueId()
                    );
                }
                break;
            case 'issn':
                if ($issn = $field->getSubfield('x')) {
                    $link = array(
                        'type' => 'isn', 'value' => trim($issn->getData()),
                        'exclude' => $this->getUniqueId()
                    );
                }
                break;
            case 'title':
                $link = array('type' => 'title', 'value' => $title);
                break;
            }
            // Exit loop if we have a link
            if (isset($link)) {
                break;
            }
        }
        // Make sure we have something to display:
        return !isset($link) ? false : array(
            'title' => $this->getRecordLinkNote($field),
            'value' => $title,
            'link'  => $link
        );
    }

    /**
     * Returns an id extracted from the identifier subfield passed in
     *
     * @param \File_MARC_Subfield $idField MARC field containing id information
     * @param string              $prefix  Prefix to search for in id field
     * @param bool                $raw     Return raw match, or normalize?
     *
     * @return string|bool                 ID on success, false on failure
     */
    protected function getIdFromLinkingField($idField, $prefix = null, $raw = false)
    {
        $text = $idField->getData();
        if (preg_match('/\(([^)]+)\)(.+)/', $text, $matches)) {
            // If prefix matches, return ID:
            if ($matches[1] == $prefix) {
                // Special case -- LCCN should not be stripped:
                return $raw
                    ? $matches[2]
                    : trim(str_replace(range('a', 'z'), '', ($matches[2])));
            }
        } else if ($prefix == null) {
            // If no prefix was given or found, we presume it is a raw bib record
            return $text;
        }
        return false;
    }

    /**
     * Get Status/Holdings Information from the internally stored MARC Record
     * (support method used by the NoILS driver).
     *
     * @param array $field The MARC Field to retrieve
     * @param array $data  A keyed array of data to retrieve from subfields
     *
     * @return array
     */
    public function getFormattedMarcDetails($field, $data)
    {
        // Initialize return array
        $matches = array();
        $i = 0;

        // Try to look up the specified field, return empty array if it doesn't
        // exist.
        $fields = $this->marcRecord->getFields($field);
        if (!is_array($fields)) {
            return $matches;
        }

        // Extract all the requested subfields, if applicable.
        foreach ($fields as $currentField) {
            foreach ($data as $key => $info) {
                $split = explode("|", $info);
                if ($split[0] == "msg") {
                    if ($split[1] == "true") {
                        $result = true;
                    } elseif ($split[1] == "false") {
                        $result = false;
                    } else {
                        $result =$split[1];
                    }
                    $matches[$i][$key] = $result;
                } else {
                    // Default to subfield a if nothing is specified.
                    if (count($split) < 2) {
                        $subfields = array('a');
                    } else {
                        $subfields = str_split($split[1]);
                    }
                    $result = $this->getSubfieldArray(
                        $currentField, $subfields, true
                    );
                    $matches[$i][$key] = count($result) > 0
                        ? (string)$result[0] : '';
                }
            }
            $matches[$i]['id'] = $this->getUniqueID();
            $i++;
        }
        return $matches;
    }

    /**
     * Return an XML representation of the record using the specified format.
     * Return false if the format is unsupported.
     *
     * @param string     $format     Name of format to use (corresponds with OAI-PMH
     * metadataPrefix parameter).
     * @param string     $baseUrl    Base URL of host containing VuFind (optional;
     * may be used to inject record URLs into XML when appropriate).
     * @param RecordLink $recordLink Record link helper (optional; may be used to
     * inject record URLs into XML when appropriate).
     *
     * @return mixed         XML, or false if format unsupported.
     */
    public function getXML($format, $baseUrl = null, $recordLink = null)
    {
        // Special case for MARC:
        if ($format == 'marc21') {
            $xml = $this->marcRecord->toXML();
            $xml = str_replace(
                array(chr(27), chr(28), chr(29), chr(30), chr(31)), ' ', $xml
            );
            $xml = simplexml_load_string($xml);
            if (!$xml || !isset($xml->record)) {
                return false;
            }

            // Set up proper namespacing and extract just the <record> tag:
            $xml->record->addAttribute('xmlns', "http://www.loc.gov/MARC21/slim");
            $xml->record->addAttribute(
                'xsi:schemaLocation',
                'http://www.loc.gov/MARC21/slim ' .
                'http://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd',
                'http://www.w3.org/2001/XMLSchema-instance'
            );
            $xml->record->addAttribute('type', 'Bibliographic');
            return $xml->record->asXML();
        }

        // Try the parent method:
        return parent::getXML($format, $baseUrl, $recordLink);
    }

    /**
     * Attach an ILS connection and related logic to the driver
     *
     * @param \VuFind\ILS\Connection       $ils            ILS connection
     * @param \VuFind\ILS\Logic\Holds      $holdLogic      Hold logic handler
     * @param \VuFind\ILS\Logic\TitleHolds $titleHoldLogic Title hold logic handler
     *
     * @return void
     */
    public function attachILS(\VuFind\ILS\Connection $ils,
        \VuFind\ILS\Logic\Holds $holdLogic,
        \VuFind\ILS\Logic\TitleHolds $titleHoldLogic
    ) {
        $this->ils = $ils;
        $this->holdLogic = $holdLogic;
        $this->titleHoldLogic = $titleHoldLogic;
    }

    /**
     * Do we have an attached ILS connection?
     *
     * @return bool
     */
    protected function hasILS()
    {
        return null !== $this->ils;
    }

    /**
     * Get an array of information about record holdings, obtained in real-time
     * from the ILS.
     *
     * @return array
     */
    public function getRealTimeHoldings()
    {
        return $this->hasILS() ? $this->holdLogic->getHoldings(
            $this->getUniqueID(), $this->getConsortialIDs()
        ) : array();
    }

    /**
     * Get an array of information about record history, obtained in real-time
     * from the ILS.
     *
     * @return array
     */
    public function getRealTimeHistory()
    {
        // Get Acquisitions Data
        if (!$this->hasILS()) {
            return array();
        }
        try {
            return $this->ils->getPurchaseHistory($this->getUniqueID());
        } catch (ILSException $e) {
            return array();
        }
    }

    /**
     * Get a link for placing a title level hold.
     *
     * @return mixed A url if a hold is possible, boolean false if not
     */
    public function getRealTimeTitleHold()
    {
        if ($this->hasILS()) {
            $biblioLevel = strtolower($this->getBibliographicLevel());
            if ("monograph" == $biblioLevel || strstr("part", $biblioLevel)) {
                if ($this->ils->getTitleHoldsMode() != "disabled") {
                    return $this->titleHoldLogic->getHold($this->getUniqueID());
                }
            }
        }

        return false;
    }

    /**
     * Returns true if the record supports real-time AJAX status lookups.
     *
     * @return bool
     */
    public function supportsAjaxStatus()
    {
        return true;
    }

    /**
     * Get access to the raw File_MARC object.
     *
     * @return File_MARCBASE
     */
    public function getMarcRecord()
    {
        return $this->marcRecord;
    }

    /**
     * Get an XML RDF representation of the data in this record.
     *
     * @return mixed XML RDF data (empty if unsupported or error).
     */
    public function getRDFXML()
    {
        return XSLTProcessor::process(
            'record-rdf-mods.xsl', trim($this->marcRecord->toXML())
        );
    }

    /**
     * Return the list of "source records" for this consortial record.
     *
     * @return array
     */
    public function getConsortialIDs()
    {
        return $this->getFieldArray('035', 'a', true);
    }

    /** Uj **
     * Define type of marc record
     * Return true for rusmarc, otherwise - false
     */
    public function is_Rusmarc ()
    {

    $marctype_fld = "marc_type";       /* Defined in books.inc & schema.xml */
    $marctype_rus = "rusmarc";
    $marctype_val = "";

    if (isset($this->fields["$marctype_fld"])): 
        $marctype_val = $this->fields["$marctype_fld"];   /* Return array ! */

        if (is_array($marctype_val)):
            $marctype_val = trim(implode($marctype_val, ""));
        endif;
    endif;

    if (strtolower($marctype_val) == strtolower($marctype_rus)
        || (count($this->getFieldArray('801')) > 0)):  /* Return arr or obj */
        $is_rusmarc = true;
    else:
        $is_rusmarc = false;
    endif;

    return $is_rusmarc;
    }


    /** Uj **
     * Perform record LEADER, position 17 
     * If not {0,1} => not confirmed record
     */
    public function getLeader17()
    {
    $leader = $this->marcRecord->getLeader();
    $pos17  = $leader[17];

    if ($pos17 != " " && $pos17 != "1"):
        return false;                               /* Not confirmed record */
    endif;

    return true;
    }


    /** Uj **
     * Convert all records of some Marc tag to assoc. array
     * Based on functions getFieldArray() & getSubfieldArray()
     * P.S. $multiSep used to separate same subfields (ex: b, b, ...)
     * Return array
     */
    public function Tag2AssocArray($marcTag, $subfields = null, $multiSep = null)
    {

    $tagArray = array();           // Result array with structured tag's data

    if (!is_array($subfields)) {
        $subfields = array();           // Default: all subfields (let be so)
    }

    $records = $this->marcRecord->getFields($marcTag); // List of tag records 
    if (!is_array($records)) {
        return $tagArray;          // Return empty array if tag doesn't exist
    }

    $j=0;                                                     // Records loop 
    foreach ($records as $currentRecord):
             $allSubfields = $currentRecord->getSubfields();

             if (count($allSubfields) > 0):
                 foreach ($allSubfields as $currentSubfield): 
                                                            // Subfields loop
                 if (in_array($currentSubfield->getCode(), $subfields)
                     || empty($subfields) == true):         // All subdields
                     $data = trim($currentSubfield->getData());
                     if (!empty($data)): 
                         $currentSubfieldCode = $currentSubfield->getCode();
                         if (!isset($tagArray[$j]["$currentSubfieldCode"])
                             || !isset($multiSep) || $multiSep == ""):
                             $tagArray[$j]["$currentSubfieldCode"]  = $data;
                         else:
                             $tagArray[$j]["$currentSubfieldCode"] .= $multiSep . $data;
                         endif;
                     endif;
                 endif;

                 endForeach;
             endif;

             $j++;
    endForeach;

    return $tagArray;
    }


    /** Uj **
     * Write authors tags 700,701,702,710,711,712 from marc record to array
     * Return array
     */
    public function RusmarcAuthors($tagsList, $fioScheme)
    {
    $tagsList  = trim($tagsList);
    $fioScheme = trim($fioScheme);

    if ($tagsList == ""):
        return;
    endif;

    $authors_arr = array();                  // Result array with authors info

    $tagsList_arr = EXPLODE(":", $tagsList);           // 700:710:701...-> arr

    $t=0;                                        // Tags loop (in given order)
    while ($t < count($tagsList_arr)):
           $authorTag = trim($tagsList_arr[$t]);

           if ($authorTag == "700" || $authorTag == "701" || $authorTag == "702"):
               $auth_arr = $this->ParseFizAuthor($authorTag, $fioScheme);
           endif;
           if ($authorTag == "710" || $authorTag == "711" || $authorTag == "712"):
               $auth_arr = $this->ParseOrgAuthor($authorTag, ", ");
           endif;

           if (is_array($auth_arr) && count($auth_arr) > 0):
               $authors_arr = array_merge($authors_arr, $auth_arr);
               unset($auth_arr);
           endif;

           $t++;
    endwhile;

    return $authors_arr;
    }


    /** Uj **
     * Parse 700, 701, 702 tags (persons)
     * Return array
     */
    public function ParseFizAuthor($authorTag, $fioScheme)
    {

    $fiz_arr = $this->Tag2AssocArray($authorTag, array('a', 'b', 'g', '4'));
                  /* P.S. Subfield '4' will be used to define author's role */

    if (!is_array($fiz_arr) || count($fiz_arr) < 1):
        return;                              /* No any author for given tag */
    endif;

    $auth_arr = array();
                             /* Perform authors according with given scheme */
    $t=0;                        
    while ($t < count($fiz_arr)):  /* Authors loop (may be several for tag) */
           if (isset($fiz_arr[$t]['a'])):
               $fiz_a = $fiz_arr[$t]['a'];
           else:
               $fiz_a = "";
           endif;
           if (isset($fiz_arr[$t]['b'])):
               $fiz_b = $fiz_arr[$t]['b'];
           else:
               $fiz_b = "";
           endif;
           if (isset($fiz_arr[$t]['g'])):
               $fiz_g = $fiz_arr[$t]['g'];
           else:
               $fiz_g = "";
           endif;
           if (isset($fiz_arr[$t]['4'])):
               $fiz_4 = $fiz_arr[$t]['4'];
           else:
               $fiz_4 = "";
           endif;

           $fiz_info = "";

           switch ($fioScheme):
             case ("ab"):
                   $fiz_info = $fiz_a . " " . $fiz_b;
                   break;

             case ("ag"):
                   $fiz_info = $fiz_a . " " . $fiz_g;
                   break;

             case ("ab[g]"):                  /* If "b" is empty => use "g" */
                   $fiz_info = $fiz_a;
                   if ($fiz_b != ""):
                       $fiz_info = $fiz_info . " " . $fiz_b;
                   endif;
                   if ($fiz_b == "" && $fiz_g != ""):
                       $fiz_info = $fiz_info . " " . $fiz_g;
                   endif;
                   break;

             default:                          /* ab(g): join all evailable */
                   $fiz_info = $fiz_a;
                   if ($fiz_b != "" && $fiz_g != ""):
                       $fiz_info = $fiz_info . " " . $fiz_b . " (" . $fiz_g . ")";
                   else:
                       $fiz_info = $fiz_info . " " . $fiz_b . " "  . $fiz_g;
                   endif;
                   break;
           endswitch;

           $fiz_info = trim($fiz_info);
                                                     /* Used for href links */
           /**
           $fiz_href = $fiz_info;
           $fiz_href = str_replace("(", "", $fiz_href);
           $fiz_href = str_replace(")", "", $fiz_href);
           **/
           $fiz_href = trim($fiz_a . " " . $fiz_b);        /* Let be so !!! */

           if ($authorTag == "702"):
               if ($fiz_4 != "" && $fiz_info != ""):
                   $roleCodes_file   = "local/import/import/rusmarc/tag702role_rusmarc_map.properties";
                   $roleCodes_prefix = "702_4_";

                   if (!isset($roleCodes_arr) || !is_array($roleCodes_arr)):
                       $roleCodes_arr = $this->TextFileCodesArray($roleCodes_file);
                   endif;

                   $roleCode = $roleCodes_prefix . $fiz_4;             /* ! */

                   if (isset($roleCodes_arr["$roleCode"])):
                       $fiz_role = $roleCodes_arr["$roleCode"];
                       $fiz_info = $fiz_info . " (" . $fiz_role . ")";
                   endif;
               endif;
           endif;

           if ($fiz_info != ""):
               $auth_arr[$t]["info"] = $fiz_info;
               $auth_arr[$t]["href"] = $fiz_href;
           endif;

           $t++;
    endwhile;

    return $auth_arr;
    }


    /** Uj **
     * Parse 710, 711, 712 tags (organizations)
     * Return array
     */
    public function ParseOrgAuthor($authorTag, $multiSep)
    {

    $org_arr = $this->Tag2AssocArray($authorTag, array('a', 'b'), $multiSep);

    if (!is_array($org_arr) || count($org_arr) < 1):
        return;                              /* No any author for given tag */
    endif;

    $auth_arr = array();
                                   /* Authors loop (may be several for tag) */
    $t=0;                        
    while ($t < count($org_arr)):
           if (isset($org_arr[$t]['a'])):
               $org_a = $org_arr[$t]['a'];
           else:
               $org_a = "";
           endif;
           if (isset($org_arr[$t]['b'])):
               $org_b = $org_arr[$t]['b'];
           else:
               $org_b = "";
           endif;

           if ($org_a != "" && $org_b != ""):
               $org_info = $org_a . ": " . $org_b;
               $org_href = $org_a . " "  . $org_b;        /* For href links */
           else:
               $org_info = $org_a .        $org_b;
               $org_href = $org_info;
           endif;

           $org_info = trim($org_info);

           if (isset($multiSep) && trim($multiSep) != ""):
               $org_href = str_replace(trim($multiSep), "", $org_href);
           endif;                       /* Prevent from deleting all spaces */

           if ($org_info != ""):
               $auth_arr[$t]["info"] = $org_info;
               $auth_arr[$t]["href"] = $org_href;
           endif;

           $t++;
    endwhile;

    return $auth_arr;
    }


    /** Uj **
     * File lines "code = name" -> array[code] = name
     * Return array
     */
    public function TextFileCodesArray($textfile_path)
    {
    $textfile_path = trim($textfile_path);

    if (!is_file($textfile_path)):
        return;
    endif;

    $textfile_arr = @file($textfile_path);             /* File lines => arr */

    if (!is_array($textfile_arr) || count($textfile_arr) < 1):
        return;                                            /* File is empty */
    endif;

    $codes_arr = array();

    $l=0;                                                /* File lines loop */
    while ($l < count($textfile_arr)):
           $line = trim($textfile_arr[$l]);

           $pos_sep = strpos($line, "=");            /* Format: code = name */
           if ($pos_sep === false || $pos_sep == 0):
               $l++;
               continue;
           endif;                    /* P.S. strpos() - remember about UTF8 */

           $code = trim(substr($line, 0, $pos_sep));
           $name = trim(substr($line, $pos_sep +1));

           $codes_arr["$code"] = $name;
           $l++;
    endwhile;

    unset($textfile_arr);

    return $codes_arr;
    }


    /** Uj **
     * Find in given Tag links for other documents
     * Links placement (ex): 452[1] -> 001RU\LSL\ADO\5
     *
     * Problem: may be many [1]-subfields for given tag
     * Theoretically link placed in 1-st of them
     */
    public function Tag45X_Clones($Tag45X, $part1_only = null)
    {
                                                                 /* Part-1 */

    $tagArray = array();          // Result array with [1]-subfield data sets

    $records = $this->marcRecord->getFields($Tag45X);  // List of tag records 
    if (!is_array($records)) {
        return $tagArray;          // Return empty array if tag doesn't exist
    }

    $multiSep = null;                                         // Let be so !
    $j=0;                                                     // Records loop 
    foreach ($records as $currentRecord):
             $allSubfields = $currentRecord->getSubfields();

             if (count($allSubfields) > 0):
                 $current_1subfield_code = "";
                 foreach ($allSubfields as $currentSubfield): 
                                                            // Subfields loop
                 $data = trim($currentSubfield->getData());
                 if (!empty($data)): 
                     $currentSubfieldCode = $currentSubfield->getCode();
                     if (trim($currentSubfieldCode) == "1"):  /* Start new  */
                         $current_1subfield_code = $data;     /* [1]-subset */
                         $current_1subfield_sub3 = substr($data,0,3);
                         $tagArray[$j]["$current_1subfield_sub3"]["$currentSubfieldCode"] = $data;
                     else:
                         if ($current_1subfield_code != ""): /*Add to subset*/
                             $current_1subfield_sub3 = substr($current_1subfield_code,0,3);
                             if (!isset($tagArray[$j]["$current_1subfield_sub3"]["$currentSubfieldCode"])
                                 || !isset($multiSep) || $multiSep == ""):
                                 $tagArray[$j]["$current_1subfield_sub3"]["$currentSubfieldCode"]  = $data;
                             else:
                                 $tagArray[$j]["$current_1subfield_sub3"]["$currentSubfieldCode"] .= $multiSep . $data;
                             endif;
                         endif;
                     endif;
                 endif;

                 endForeach;
             endif;

             $j++;
    endForeach;

    if (isset($part1_only) && trim($part1_only) != ""):                /* ! */
        return $tagArray;
    endif;
                                                                  /* Part-2 */

    if (!is_array($tagArray) || count($tagArray) < 1):
        return;                           /* No any record with subfield #1 */
    endif;

    $clones_arr = array();                                  /* Result array */
          
    $j=0;                          /* Records loop (may be several for tag) */
    while ($j < count($tagArray)):
           if (isset($tagArray[$j]["001"]["1"])):
               $cloneLink = trim($tagArray[$j]["001"]["1"]);
               $cloneLink = substr($cloneLink, 3);      /* Cut prefix "001" */
           else:
               $cloneLink = "";
           endif;
           if (isset($tagArray[$j]["200"]["a"])):
               $cloneName = trim($tagArray[$j]["200"]["a"]);
           else:
               $cloneName = "";
           endif;
           if (isset($tagArray[$j]["200"]["b"])):
               $cloneKind = trim($tagArray[$j]["200"]["b"]);
           else:
               $cloneKind = "";
           endif;

           if (trim($cloneLink . $cloneName . $cloneKind) == ""):
               $j++;
               continue;
           endif;
                                  /* Perform links in style of "lslkey.bsh" */
           if ($cloneLink != ""):
               $lslkey = "";

               $i=0;
               while ($i < strlen($cloneLink)):
                      $c = substr($cloneLink, $i, 1);

                      if (!(($c >= '1' && $c <= '9') ||
                            ($c >= 'A' && $c <= 'Z') || ($c >='a' && $c <= 'z') || 
                            ($c >= 'А' && $c <= 'Я') || ($c >='а' && $c <= 'я'))):

                          $s = dechex(ord($c));

                          switch (strlen($s)):
                            case (1): 
                                  $s = "00" . $s; 
                                  break;
                            case (2): 
                                  $s = "0"  . $s; 
                                  break;
                          endswitch;

                          $s = STRTOUPPER($s);  /* Rodion (for "\": c -> C) */
                          $lslkey .= $s;
                      else:
                          $lslkey .= $c;
                      endif;

                      $i++;
               endwhile;

               $cloneLink = trim($lslkey);
           endif;

           $clones_arr[$j]["cloneLink"] = $cloneLink;
           $clones_arr[$j]["cloneName"] = $cloneName;
           $clones_arr[$j]["cloneKind"] = $cloneKind;

           $j++;
    endwhile;

    return $clones_arr;
    }


    /** Uj **
     * Return char from given position of LEADER record
     *
     */
    public function getLeaderPos($pos)
    {
    $pos = intval($pos);
    $leader = $this->marcRecord->getLeader();

    if (isset($leader[$pos])):
        $leader_pos = $leader[$pos];
    else:
        $leader_pos = "";
    endif;

    return $leader_pos;
    }


    /** IL **
     * Covers (images)
     *
     */
    public function getThumbnail($size='small')
    {
    	//обложки для авторефератов http://z3950.kpfu.ru/referat/avtoref_cov.jpg
    	$info105 = $this->marcRecord->getField('105');
    	if ($info105<>null) {	
    		$info105a=$info105->getSubfield('a');
    		if ($info105a<>null && strpos($info105a,'d')==9) 
    			return "http://z3950.ksu.ru/referat/avtoref_cov.jpg";
    	}

    $ebooks_icon_url = "http://libweb.kpfu.ru/ebooks/icon/";          /* Uj */
    $temp1 = $this->marcRecord->getField('856');
    if ($temp1<>null) {  
        $temp=$temp1->getSubfield('a');
            //RUCONT
        if ($temp<>null && strpos($temp,'ucont')>0) 
            return $ebooks_icon_url . "RUKONT.jpg";
        //ZNANIUM
        $temp=$temp1->getSubfield('u');
        if ($temp<>null && strpos($temp,'znanium')>0)
            return $ebooks_icon_url . "znanium3.jpg";
        //LANM
        if ($temp<>null && strpos($temp,'e.lanbook')>0)
            return $ebooks_icon_url . "lan1.jpg";
        //bibliorossica
        if ($temp<>null && strpos($temp,'bibliorossica')>0)
            return $ebooks_icon_url . "biblioros3.jpg";
    }

    	

    	$info856 = $this->getPdfLink();
    
    //обложки для учебно метод материалов http://libweb.kpfu.ru/ebooks/icon/KFU_UM_1.jpg
    $info001 = current($this->marcRecord->getFields('001'));
    if ($info001<>null && strpos($info001,'\\LSL\\EOR\\')>0) {
            $ad=$info856;
            $pos2=strpos(strtolower($ad),'.pdf');
            if ($pos2>0){
                $pos2=strpos($ad,'.pdf');
                $url=substr($ad,0,$pos2);
                $url=$url.'_cov.jpg';
                $status='#^HTTP/1\.[01] (?:2\d\d|3\d\d)#';
                $status1='#^HTTP/1\.[01] (?:404)#';
                $result = get_headers($url);
                if (preg_match($status1,$result[7] ))
    				{
                   return $ebooks_icon_url . "KFU_UM_1.jpg";
    				}

                if (!@fopen($url, "r")):       /* Uj: is cover file exist ? */
                    return $ebooks_icon_url . "KFU_UM_1.jpg";
                endif;

                //if (preg_match($status,$result[0] ))
    				//{
                   return $url;
    				//}
            }
            return $ebooks_icon_url . "KFU_UM_1.jpg";
    }


    	$info035 = $this->marcRecord->getFields('035');
    	$info773 = $this->marcRecord->getFields('773');
    	$info022 = $this->marcRecord->getFields('022');
    	$info260 = $this->marcRecord->getFields('260');
    	$info362 = $this->marcRecord->getFields('362');
    	$tmp=current($this->marcRecord->getFields('001')).current($info035).current($info773);
    	$info022 = preg_replace('/[^0-9]/i','',$info022);
    	$urls=$this->getUrls();

    	//Каталог библиотеки
    	//if (strpos($info001,'Books')>0 && strpos($info001,'RU')>=0 && strpos($info001,'LSL')>0) {
    	if ((strpos($info001,'RU')>=0 && strpos($info001,'LSL')>0)||(strpos($info001,'LIBNET')>=0)) {
    			$ad=$info856;
    			
    			$pos1=strpos(strtolower($ad),'_con.pdf');
    			$pos2=strpos(strtolower($ad),'.pdf');
    			$pos3=strpos(strtolower($ad),'libweb.kpfu.ru');
    			if ($pos3<=0) $pos3=strpos(strtolower($ad),'libweb.ksu.ru');
    			$pos4=strpos(strtolower($ad),'er');
    			$pos5=strpos(strtolower($ad),'el_resources');
    			if ($pos1>0) {
    				$url=substr($ad,0,$pos1);
    				$url=$url.'_cov.jpg';
    				//echo $url;
    				return $url;
    			} else if ($pos2>0){
    				$pos2=strpos($ad,'.pdf');
    				$url=substr($ad,0,$pos2);
    				$url=$url.'_cov.jpg';
    				//echo $url;
    				return $url;
    			} else if ($pos3>0 && $pos4>0) {
    				$ad=$ad.'/_cv/full/!100,100/0/native.jpg';
    				//echo $ad;
    				//echo $ad;
    				return $ad;
    			} else if ($pos3>0 && $pos5>0) {
    				$ad=str_replace('el_resources','er',$ad);
    				$ad=$ad.'/_cv/full/!100,100/0/native.jpg';
    				//echo $ad;
    				return $ad;
    			} else if ($isbn = $this->getCleanISBN()) {
    					return array('isn' => $isbn, 'size' => $size);
    			} 
    			//echo "false\n";
    			return false;
    	}
    	//Электронные журналы и книги
    	else if (strpos($tmp,'ebr')>0) {
    		if (count($urls)>0) {
    			$ad=$urls[0]["url"];
            $rest = substr($ad, 42);
            $url='http://site.ebrary.com/lib/kazanst/cover.action?docID='.$rest;
    			return $url;
    		}else if ($isbn = $this->getCleanISBN()) {
    					return array('isn' => $isbn, 'size' => $size);
    				}
    				return false;
    	} else if (strpos($tmp,'ejournal')>0){
    			if(count($urls)>0&&strpos($urls[0]["url"],'sciencedirect')>0){
    				if (count($urls)>0) {
    				$rest = substr($urls[0]["url"], 45);
    				$url='http://ars.els-cdn.com/content/image/S'.$rest .'.gif';
    				return $url;
    			}else if ($isbn = $this->getCleanISBN()) {
    					return array('isn' => $isbn, 'size' => $size);
    					}
    				return false;
    			}else if(count($urls)>0 && strpos($urls[0]["url"],'jstor')>0){
                                    if (count($urls)>0) {
                                    $rest = substr($urls[0]["url"], 56);
                                    $url='http://www.jstor.org/literatum/publisher/jstor/journals/covergifs/'.$rest .'/cover.gif';
                                    return $url;
                                    }else if ($isbn = $this->getCleanISBN()) {
    										return array('isn' => $isbn, 'size' => $size);
    										}
    									return false;

                            }else if(count($urls)>0 && strpos($urls[0]["url"],'cambridge')>0){
                                    if (count($urls)>0) {
    										$rest = substr($urls[0]["url"], 57);
    										$rest = mb_convert_case($rest, MB_CASE_UPPER, "UTF-8");
    										$url='http://journals.cambridge.org/cover_images/'.$rest.'/'.$rest .'.jpg';
    										return $url;
                                    }else  if ($isbn = $this->getCleanISBN()) {
    										return array('isn' => $isbn, 'size' => $size);
    										}
    									return false;
                            }else if(count($urls)>0 && strpos($urls[0]["url"],'iopscience')>0){
                                    if (count($urls)>0) {
    										$rest = substr($urls[0]["url"], 26);
    										//if(fopen('http://images.iop.org/journals_icons/Info/'.$rest .'/cover.jpg',"r"))
    										$url='http://images.iop.org/journals_icons/Info/'.$rest .'/cover.jpg';
    										$testheader=get_headers($url);
    										if ($testheader[0]<>"HTTP/1.1 200 OK")
    										{ 
    											$url='http://images.iop.org/journals_icons/Info/'.$rest .'/cover.gif';
    											$testheader=get_headers($url);
    											if ($testheader[0]=="HTTP/1.1 200 OK")
    												return $url;
    										}
    										else return $url;
                                    }else  if ($isbn = $this->getCleanISBN()) {
    										return array('isn' => $isbn, 'size' => $size);
    									}
    									return false;
                            }else if(count($urls)>0 && strpos($urls[0]["url"],'pubs.acs.org')>0){
                                    if (count($urls)>0) {
    										$rest = substr($urls[0]["url"], 24);
    										$random_volume =2014 - substr(preg_replace('/[^0-9]/i','',$info362[0]),8);
    										$url="http://pubs.acs.org/appl/literatum/publisher/achs/journals/content/".$rest."/2013/". $rest.".2013.".$random_volume.".issue-1/".$rest.".2013.".$random_volume.".issue-1/production/".$rest.".2013.".$random_volume.".issue-1.largecover.jpg";
    										return $url;
    									}else  if ($isbn = $this->getCleanISBN()) {
    										return array('isn' => $isbn, 'size' => $size);
    										}
    									return false;
                            }else if(count($urls)>0 && strpos($urls[0]["url"],'link.aip.org')>0){
                                    if (count($urls)>0) {
    										$rest = substr($urls[0]["url"], 26);
    										$random_volume =2014 - substr(preg_replace('/[^0-9]/i','',$info362[0]),-6,4);
    										$url="http://online.medphys.org/free_media/issue_files/".$rest."/tn".$random_volume."-1.jpg";
    										return $url;
                                    }else  if ($isbn = $this->getCleanISBN()) {
    										return array('isn' => $isbn, 'size' => $size);
    										}
    									return false;
                            }else if(count($urls)>0 && strpos($urls[0]["url"],'elibrary.ru')>0){
                                    if (count($urls)>0) {
                                    $url="http://elibrary.ru/images/menu_journ.jpg";
                                    if ($isbn = $this->getCleanISBN()) {
    										return array('isn' => $isbn, 'size' => $size);
    										}
    									return $url;
                                    }else  if ($isbn = $this->getCleanISBN()) {
    										return array('isn' => $isbn, 'size' => $size);
    										}
    									return false;
                            }else if(count($urls)>0 && strpos($urls[0]["url"],'www.sciencemag.org')>0){
                                    if (count($urls)>0) {
                                    $url="http://www.sciencemag.org/site/icons_shared/sci-assets.png";
                                    return $url;
                                    }else  if ($isbn = $this->getCleanISBN()) {
    										return array('isn' => $isbn, 'size' => $size);
    										}
    									return false;
                            }else if(count($urls)>0 && strpos($info856[0],'oxfordjournals')>0){
                                    if (count($urls)>0) {
                                    $url=$info856[0].'/widget/public/current-issue/cover.gif';
                                    return $url;
                                    }else  if ($isbn = $this->getCleanISBN()) {
    										return array('isn' => $isbn, 'size' => $size);
    										}
    									return false;
                            }else  if ($isbn = $this->getCleanISBN()) {
    										return array('isn' => $isbn, 'size' => $size);
    										}
    									return false;


            }else  if ($isbn = $this->getCleanISBN()) {
    										return array('isn' => $isbn, 'size' => $size);
    										}
    									return false;

    }	

    /** IL **/
    //возвращает значение поля 856u
    public function getPdfLink()
    {
        $retVal = array();

        // Which fields/subfields should we check for URLs?
        $fieldsToCheck = array(
            //'856' => array('y', 'z'),   // Standard URL
            //'555' => array('a'),         // Cumulative index/finding aids
			'856'=>array('u')
        );

        foreach ($fieldsToCheck as $field => $subfields) {
            $urls = $this->marcRecord->getFields($field);
            if ($urls) {
                foreach ($urls as $url) {
                    // Is there an address in the current field?
                    $address = $url->getSubfield('u');
                    if ($address) {
                        $address = $address->getData();

                        // Is there a description?  If not, just use the URL itself.
                        foreach ($subfields as $current) {
                            $desc = $url->getSubfield($current);
                            if ($desc) {
                                break;
                            }
                        }
                        if ($desc) {
                            $desc = $desc->getData();
                        } else {
                            $desc = $address;
                        }

                        $retVal[] = array('url' => $address, 'desc' => $desc);
                    }
                }
            }
        }
		
		if (count($retVal)>0) {
			 return $retVal[0]["url"];}
		else return null;
    }	


}
