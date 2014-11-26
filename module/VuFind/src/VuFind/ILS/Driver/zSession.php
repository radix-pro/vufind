<?php

namespace VuFind\ILS\Driver;

use SimpleXMLElement,
    DateTime;

class zTool {
    
    public static $zURL = "http://192.168.20.25/zgate/xgate";
    
    private static $localLocations = array(
        'КХ' => 'Книгохранение (ул. Кремлевская, 35)',
        'КХ1' => 'Книгохранение старое здание (ул. Кремлевская, 13)',
        'ЧЗ1' => 'Читальный зал 1 (ул. Кремлевская, 35)',
        'ЧЗ2' => 'Читальный зал 2 (ул. Кремлевская, 35)',
        'ЧЗ3' => 'Читальный зал 3 (ул. Кремлевская, 35)',
        'ЧЗ4' => 'Читальный зал 4 (ул. Кремлевская, 35)',
        'ЧЗ5' => 'Читальный зал 5 (ул. Кремлевская 29/1)',
        'ЧЗ7' => 'Читальный зал 7 (ул. Кремлевская, 4/5)',
        'ЧЗ8' => 'Читальный зал 8(ул. Красная позиция, 2а)',
        'ЧЗ9' => 'Читальный зал 9 (ул. Кремлевская, 16А)',
        'ЧЗ10' => 'Читальный зал 10 (ул. Кремлевская, 13)',
        'ЧЗ11' => 'Читальный зал института филологии и искусства (ул. Татарстан, 2)',
        'ЧЗ12' => 'Читальный зал 12 Института истории (ул. К.Маркса, 74)',
        'ЧЗ13' => 'Универсальный зал института экономики и финансов (ул. Бутлерова, 4)',
        'ЧЗ14' => 'Универсальный зал института филологии и искусства (ул. Татарстан, 2)',
        'Абн' => 'Абонемент (ул. Кремлевская, 35)',
        'Абн1' => 'Абонемент факультета иностранных языков (ул. Татарстан, 2)',
        'Абн2' => 'Абонемент факультета русской филологии (ул. Татарстан, 2)',
        'Абн3' => 'Абонемент факультета искусств и музыкального образования (ул. М. Межлаука, 3)',
        'Абн4' => 'Абонемент факультета психолого-педагогического образования (ул. Татарстан, 2)',
        'Абн5' => 'Абонемент факультета физического образования и дизайна (ул.Лево-булачная, 44)',
        'Абн7' => 'Абонемент геофака (ул. Кремлевская, 4/5)',
        'Абн9' => 'Абонемент факультета татарской филологии (ул. Татарстан, 2)',
        'Абн10' => 'Учебный абонемент института экономики и финансов (Бутлерова, 4)',
        'Абн11' => 'Научный абонемент института экономики и финансов (Бутлерова, 4)',
        'ХАбн' => 'Художественный абонемент  (ул. Кремлевская, 13)',
        'ХАбн2' => 'Художественный абонемент 2  (ул. Татарстан, 2)',
        'КАбн' => 'Коллективный абонемент  (ул. Кремлевская, 35)',
        'КК2' => 'Компьютерный класс N2  (ул. Кремлевская, 35)',
        'ОРРК' => 'Отдел рукописей и редких книг (ул. Кремлевская, 13)',
        'НБО' => 'Научно-библиографический отдел  (ул. Кремлевская, 35)',
        'МО' => 'Методический отдел  (ул. Кремлевская, 35)',
        'ОНТОЛ' => 'Отдел научной и технической обработки  (ул. Кремлевская, 35)',
        'ОК' => 'Отдел комплектования (ул. Кремлевская, 35)',
        'ЦЕД' => 'Центр европейской документации (ул. Кремлевская, 35)',
        'ОА' => 'Отдел автоматизации (ул. Кремлевская, 35)',
        'СпецФ' => 'Спец фонд (ул. Кремлевская, 35)',
        'ОДФ' => 'Обменно-дублетный фонд (ул. Кремлевская, 35)',
        'ДУ' => 'Деревня Универсиады'
    );
    private static $nucCodes = array(
        '42013097' => 'НБ КФУ',
        'НБ_КГУ' => 'НБ КФУ',
        'НБ КФУ' => 'НБ КФУ'
    );

    public static $idPfx = array(
        array("RU\\LSL\\Books\\", "RU\\LSL\\Books\\", "BOOKS"),
        array("RU\\LSL\\BOOKS02\\", "RU\\LSL\\BOOKS02\\", "BOOKS02"),
        array("RU\\LSL\\SAL_3\\", "RU\\LSL\\SAL_3\\", "SAL_3"),
        array("RU\\LSL\\SAL_4\\", "RU\\LSL\\SAL_4\\", "SAL_4"),
        array("RU\\LSL\\EXT_KX\\", "RU\\LSL\\EXT_KX\\", "EXT_KX"),
        array("RU/ФБ ТГГПУ/OSN/", "RU/ФБ ТГГПУ/OSN/", "OSN"),
        array("RU/ФБ ТГГПУ/BB/", "", "OSN"),
        array("RU\\LSL\\NBO\\", "RU\\LSL\\NBO\\", "NBO")
        
    );

    public static function ParseId($id) {
        foreach (self::$idPfx as $i) {
            if ( strpos($id, $i[0]) === 0 )
            {
                return array( 'pfx' => $i[1], 'key' => substr($id, strlen($i[1])), 'db' => $i[2] );
            }
        }
        return null;
    }

    public static function LocationString($nucCode, $localLocation) {
        if (!empty($nucCode) && array_key_exists($nucCode, self::$nucCodes))
            $n = self::$nucCodes[$nucCode];
        else
            $n = 'External';
        if (!empty($localLocation) && array_key_exists($localLocation, self::$localLocations))
            $l = self::$localLocations[$localLocation];
        else
            $l = 'Unknown location';
        //return sprintf("%s, %s", $n, $l);
        if ($n != 'External')
            return sprintf("%s", $l);
        else
            return sprintf("%s, %s", $n, $l);
    }

    private static $diagstring = array(
        1 => "Permanent system error",
        2 => "Temporary system error",
        3 => "Unsupported search",
        4 => "Terms only exclusion (stop) words",
        5 => "Too many argument words",
        6 => "Too many boolean operators",
        7 => "Too many trucated words",
        9 => "Trucated words too short",
        11 => "Too many characters in search statement",
        12 => "Too many records retrieved",
        13 => "Present request out-of-range",
        14 => "System error in presenting records",
        23 => "Specified combination of databases not supported",
        27 => "Result set no longer exists - unilaterally deleted by target",
        28 => "Result set is in use",
        29 => "One of the specified databases is locked",
        30 => "Specified result set does not exist",
        31 => "Resourses exhausted - no results available",
        32 => "Resourses exhausted - unpredictable partial results available",
        33 => "Resourses exhausted - valid subset of results available",
        100 => "(Unspecified) error",
        101 => "Access-control failure",
        106 => "No abstract syntaxes agreed to for this record",
        107 => "Query type not supported",
        108 => "Malformed query",
        109 => "Database unavailable",
        110 => "Operator unsupported",
        111 => "Too many databases specified",
        112 => "Too many result sets created",
        113 => "Unsupported attribute type",
        114 => "Unsupported Use attribute",
        115 => "Unsupported term value for Use attribute",
        116 => "Use attribute required but not supplied",
        117 => "Unsupported Relation attribute",
        118 => "Unsupported Structure attribute",
        119 => "Unsupported Position attribute",
        120 => "Unsupported Truncation attribute",
        121 => "Unsupported Attribute Set",
        122 => "Unsupported Completeness Attribute",
        123 => "Unsupported attribute combination",
        125 => "Malformed search term",
        126 => "Illegal term value for attribute",
        205 => "Only zero step size supported for Scan",
        206 => "Specified step size not supported for Scan",
        219 => "No such package, on modify/delete",
        220 => "Quota exceeded",
        221 => "Service not supported",
        222 => "Permission denied - id not authorized",
        223 => "Permission denied - cannot modify or delete",
        235 => "Database does not exist",
        236 => "Access to specified database denied",
        238 => "Record not available in requested syntax",
        239 => "Record syntax not supported",
        240 => "Scan: Resources exhausted looking for satisfying terms",
        1025 => "Service not supported for this database",
        1026 => "Record cannot be opened because it is locked",
        1027 => "SQL error",
        1028 => "Record deleted",
        1040 => "ES: Invalid function",
        1041 => "ES: Error in retention time",
        1042 => "ES: Permissions data not understood",
        1043 => "ES: Invalid OID for task specific parameters",
        1044 => "ES: invalid action",
        1045 => "ES: Unknown schema",
        1046 => "ES: Too many records in package",
        1047 => "ES: Invalid wait action",
        1056 => "Attribute not supported for database",
        1058 => "Duplicate Detection: Cannot dedup on requested record portion",
        1059 => "Duplicate Detection: Requested detection criterion not supported",
        1060 => "Duplicate Detection: Requested level of match not supported",
        1061 => "Duplicate Detection: Requested regular expression not supported",
        1062 => "Duplicate Detection: Cannot do clustering",
        1063 => "Duplicate Detection: Retention criterion not supported",
        1064 => "Duplicate Detection: Requested number (or percentage) of entries for retention too large"
    );

    public static function DiagString($code) {
        return array_key_exists($code, self::$diagstring) ? self::$diagstring[$code] : 'unspecified error';
    }

    static function TaskStatus($str) {
        switch ($str) {
            default:
                return 'unknown';
            case '0':
                return 'accepted';
            case '1':
                return 'completed';
            case '2':
                return 'issued';
            case '3':
                return 'rejected';
        }
    }

    static function StrInfo($str) {
        $info = array();
        if (preg_match('/: (.+),.+: (.+),(.+)/', $str, $tmp)) {
            $info['checkout'] = DateTime::createFromFormat("d.m.Y", $tmp[1]);
            $info['duedate'] = DateTime::createFromFormat("d.m.Y", $tmp[2]);
            $info['note'] = $tmp[3];
        }
        return $info;
    }

    static function SerialEscape($serial) {
        return preg_replace('/([^1-9A-Za-zА-Яа-я])/e', 'sprintf("0%02X",ord("\\1"))', $serial);
    }

    static function SerialUnEscape($serial) {
        return preg_replace('/0(..)/e', 'chr(0x\1)', $serial);
    }

    static function Post($zparam) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::$zURL);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_COOKIE, "ZGATE_TARGET=books.xml;ZGATE_PROFILE=init_xml.xsl");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $zparam);        
        $result = sprintf('<object>%s</object>', curl_exec($ch));
        return $result;
    }

    static function Get($zparam) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf("%s?%s", self::$zURL, $zparam));
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_COOKIE, "ZGATE_TARGET=books.xml;ZGATE_PROFILE=init_xml.xsl");
        $result = sprintf("<object>%s</object>", curl_exec($ch));
        return $result;
    }

    static function xToObj($xml) {
        try {
            return new SimpleXMLElement($xml, LIBXML_ERR_WARNING | LIBXML_NOWARNING | LIBXML_NOERROR);
        } catch (Exception $e) {
            return null;
        }
    }

}

class zResultSet {

    private $sxmlobj = null;
    private $size = 0;
    private $ready = false;

    public function __get($property) {
        switch ($property) {
            default:
                return null;
            case 'ready':
                return $this->ready;
            case 'size':
                return $this->size;
            case 'record':
                return (isset($this->sxmlobj) && count($this->sxmlobj->xpath('//record')) > 0) ? current($this->sxmlobj->xpath('//record')) : null;
            case 'records':
                return (isset($this->sxmlobj) && count($this->sxmlobj->xpath('//record')) > 0) ? $this->sxmlobj->xpath('//record') : null;
            case 'holdingsData':
                return (isset($this->sxmlobj) && count($this->sxmlobj->xpath('//record/holdingsData/holdingsAndCirc')) > 0) ? $this->sxmlobj->xpath('//record/holdingsData/holdingsAndCirc') : null;
            case 'bibliographicRecord':
                return (isset($this->sxmlobj) && count($this->sxmlobj->xpath('//record/bibliographicRecord')) > 0) ? current($this->sxmlobj->xpath('//record/bibliographicRecord')) : null;
            case 'full':
                return (isset($this->sxmlobj)) ? $this->sxmlobj : null;
        }
    }

    function __construct($query) {
        try {
            $this->sxmlobj = zTool::xToObj(zTool::Post($query));
        } catch (Exception $e) {
            return;
        }
        $failed = intval(current($this->sxmlobj->xpath('resultSetStatus')));
        $size = intval(current($this->sxmlobj->xpath('resultCount')));
        if ($failed > 0 || $size < 1) {
            $this->ready = false;
            $this->size = 0;
        } else {
            $this->ready = true;
            $this->size = $size;
        }
    }

}

class zSession {

    private $session;
    private $resultset;
    private $order;
    private $username;
    private $password;
    private static $errorstring = array(
        1 => 'The session has expired',
        2 => 'Failed to initialize session',
        3 => 'Insufficient number of parameters',
        4 => 'XSL template not found',
        5 => 'Variable not found',
        6 => 'Session is busy',
        7 => 'XML Parsing Error',
        8 => 'XSLT Parsing Error',
        100 => '(Unspecified) error',
        101 => 'Invalid database name',
        9998 => 'Invalid username or password'
    );

    public function __get($property) {
        switch ($property) {
            default:
                return null;
            case 'ready':
                return (isset($this->session)) ? true : false;
            case 'id':
                return (isset($this->session)) ? strval($this->session->id) : null;
            case 'username':
                return (isset($this->username)) ? $this->username : null;
            case 'password':
                return (isset($this->password)) ? $this->password : null;
            case 'resultset':
                return (isset($this->resultset)) ? $this->resultset : null;
            case 'order':
                return (isset($this->order)) ? $this->order : null;
            case 'error':
                return (isset($this->error)) ? $this->error : null;
            case 'errormsg':
                return isset($this->error) ? isset(self::$errorstring[$this->error]) ? self::$errorstring[$this->error] : 'Unknown error' : null;
            case 'databases':
                return (isset($this->session)) ? strval($this->session->databases) : null;
        }
    }

    private function getRSet($query) {
        unset($this->error);
        $this->resultset = new zResultSet($query);
        if ($this->resultset->ready)
            return true;
        else {
            $resultset = $this->resultset->full;
            if (!is_null($resultset)) {
                $error = strval(current($resultset->xpath('errormsg')));
                if (empty($error))
                    $error = strval(current($resultset->xpath('error/@id')));
                $this->error = empty($error) ? 100 : $error;
            } else
                $this->error = 100;
            return false;
        }
    }

    public function SearchById($id, $database = null, $maxRecords = '1') {
        if ( empty($database) ) {
           if ($i = zTool::ParseId($id))
           {
              $database = $i['db'];
              $id = $i['key'];
           } else {
              return null;
              //$database = $this->session->databases;
           }
        }
        $query = sprintf("HOST=%s&PORT=%s&SESSION_ID=%s&ACTION=SEARCH&ESNAME=F&DBNAME=%s&USE_1=12&REL_1=3&TERM_1=%s&STRUCT_1=1&SHOW_HOLDINGS=on&MAXRECORDS=%s",
                         $this->session->host, $this->session->port, $this->session->id,
                         $database, $id, $maxRecords );
        return $this->getRSet($query);
    }

    public function SearchItems($maxRecords = '1024') {
        if (!(isset($this->session) && isset($this->username)))
            return false;
        $query = sprintf("ACTION=SEARCH&HOST=%s&PORT=%s&SESSION_ID=%s&ESNAME=F&ATTSET=1.2.840.10003.3.3&DBNAME=CIRC&use_1=100&term_1=%s&MAXRECORDS=%s", $this->session->host, $this->session->port, $this->session->id, $this->username, $maxRecords
        );
        return $this->getRSet($query);
    }

    public function SearchOrders($maxRecords = '1024') {
        if (!(isset($this->session) && isset($this->username)))
            return false;
        $query = sprintf("ACTION=SEARCH&HOST=%s&PORT=%s&SESSION_ID=%s&ESNAME=F&ATTSET=1.2.840.10003.3.3&DBNAME=IR-EXTEND-1&use_1=1&term_1=%s&MAXRECORDS=%s", $this->session->host, $this->session->port, $this->session->id, $this->username, $maxRecords
        );
        return $this->getRSet($query);
    }

    public function SearchUserInfo() {
        if (!( isset($this->session) && isset($this->username) ))
            return false;
        $query = sprintf("ACTION=SEARCH&HOST=%s&PORT=%s&SESSION_ID=%s&ESNAME=F&ATTSET=1.2.840.10003.3.3&DBNAME=LUSR&use_1=100&term_1=%s&MAXRECORDS=1", $this->session->host, $this->session->port, $this->session->id, $this->username
        );
        return $this->getRSet($query);
    }

    public function Present($start, $count) {
        if (!(isset($this->resultset) && $this->resultset->ready))
            return null;
        $sigma = $this->resultset->size + 1 - $start - $count;
        if ($sigma < 0)
            $count += $sigma;
        $query = sprintf("present+%s+default+%s+%s+X+!+eng", $this->session->id, $start, $count
        );
        return zTool::xToObj(zTool::Get($query));
    }

    private function isSession() {
        if (is_null($this->session) ||
                count($this->session->xpath('error')) != 0 ||
                count($this->session->xpath('id')) != 1)
            return false;
        else
            return true;
    }

    public function Init($id = null) {
        if (is_null($id) && isset($this->session)) 
            $id = $this->session->id;
        unset($this->session);
        unset($this->resultset);
        unset($this->order);
        unset($this->error);
        if (! is_null($id))
        {
            $param = sprintf("SESSION_ID=%s&ACTION=SEARCH&DBNAME=NULL&TERM_1=S", $id);
            $answer = zTool::xToObj(zTool::Post($param));
            $error = strval(current($answer->xpath('errormsg')));
            if (empty($error)) $error = strval(current($answer->xpath('error/@id')));
            if (empty($error)) 
            {                
                $param = sprintf("form+%s+books.xml+init_xml.xsl+eng", $id);
                $this->session = zTool::xToObj(zTool::Get($param));                
                if ($this->isSession()) return true;
            }
        }
        if (isset($this->session)) {
            $error = strval(current($this->session->xpath('errormsg')));
            if (empty($error))
                $error = strval(current($this->session->xpath('error/@id')));
            if (empty($error) || $error != 1) {
                $this->error = empty($error) ? 100 : $error;
                unset($this->session);
                return false;
            }
            unset($this->session);
        }
        $param = "ACTION=INIT&FORM_HOST_PORT=books.xml,init_xml.xsl";
        if (isset($this->username))
            $param = sprintf(
                    "%s&USERID=%s&PASSWORD=%s", $param, $this->username, isset($this->password) ? $this->password : null
            );
        $this->session = zTool::xToObj(zTool::Post($param));
        if (!$this->isSession()) {
            $error = strval(current($this->session->xpath('errormsg')));
            if (empty($error))
                $error = strval(current($this->session->xpath('error/@id')));
            $this->error = empty($error) ? 100 : $error;
            unset($this->session);
            return false;
        }
        return true;
    }

    function __construct($username = null, $password = null, $id = null) {
        if (!is_null($username))
            $this->username = $username;
        if (!is_null($password))
            $this->password = $password;
        $this->Init($id);
    }

    private function Process($param) {
        $this->order = zTool::xToObj(zTool::Post($param));
        return count($this->order->xpath('//record/targetReference')) ? true : false;
    }

    function OrderToHold($nucCode, $localLocation, $placesInQueue = 3, $volumeIssue = null, $requesterNote = null, $circDesk = null) {
        if (!empty($localLocation)) { $localLocation = '/'.$localLocation; }
        if (!empty($circDesk)) { $circDesk = '/'.$circDesk; }
        if (isset($this->session) && isset($this->resultset) && $this->resultset->ready) {
            $param = sprintf("RSNAME=default&START=1&SESSION_ID=%s&STAGE=3&VOLUME_ISSUE=%s&REQUESTER_NOTE=%s&ILL_SERVICE=1&IO_LOCATION=%s%s%s&PLACE_ON_HOLD=%s&ACTION=ORDER", 
                                $this->session->id, $volumeIssue, $requesterNote, 
                                $nucCode, $localLocation, $circDesk, 
                                $placesInQueue );
            return $this->Process($param);
        }
    }

    function OrderToGetCopy($medium = 1, $pagination = null, $volumeIssue = null) {
        if (isset($this->session) && isset($this->resultset) && $this->resultset->ready) {
            $param = sprintf("RSNAME=default&START=1&LANG=eng&SESSION_ID=%s&STAGE=3&VOLUME_ISSUE=%s&ILL_SERVICE=2&PAGINATION=%s&MEDIUM=%s&ACTION=ORDER", $this->session->id, $volumeIssue, $pagination, $medium
            );
            return $this->Process($param);
        }
    }

}
