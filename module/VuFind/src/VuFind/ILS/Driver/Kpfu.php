<?php

namespace VuFind\ILS\Driver;

use Zend\Session\Container as SessionContainer,
    \DateTime;

class Kpfu extends AbstractBase
{    
    protected $session;
    
    public function __construct() 
    {
    }

    public function init()
    {
        $this->session = new SessionContainer('KFUDriver');
        if (!isset($this->session->zs)) $this->session->zs = array();
    }
    
    protected function freeSession($zs)
    {
        for ($i = 0; $i < count($this->session->zs); $i++)
        {
            if ($this->session->zs[$i]['id'] == $zs->id)
            {
                $this->session->zs[$i]['free'] = true;
                break;
            }
        }
    }    
    
    protected function takeSession($username = null, $password = null)
    {
        for ($i = 0; $i < count($this->session->zs); $i++)
        {
            if ($this->session->zs[$i]['username'] == $username 
                    && $this->session->zs[$i]['password'] == $password 
                    && $this->session->zs[$i]['free'])
            {
                $this->session->zs[$i]['free'] = false;
                $zs = new zSession($username, $password, $this->session->zs[$i]['id']);
                $zs->Init();
                if (!$zs->ready) unset($this->session->zs[$i]);
                else $this->session->zs[$i]['id'] = $zs->id;
                return $zs;
            }
        }
        $zs = new zSession($username, $password);
        if ($zs->ready)
        {
            $this->session->zs[] = array(
                'id'        => $zs->id,
                'username'  => $username,
                'password'  => $password,
                'free'      => false
            );
        }
        return $zs;
    }

    public function getStatus($id)
    {
        return $this->getHolding($id);
    }

    public function getStatuses($ids)
    {
        $sts = array();                
        $zs = $this->takeSession();
        if ($zs->ready) 
        {
            foreach ($ids as $id) {
                $st = array();
                if ($zs->SearchById(zTool::SerialUnEscape($id)))
                {               
                    foreach ($zs->resultset->holdingsData as $h) 
                    {   
                        $a = intval(current($h->xpath('circulationData/circRecord/availableNow')));  
                        $l = strval(current($h->xpath('localLocation')));
                        $n = strval(current($h->xpath('nucCode')));
                        $c = strval(current($h->xpath('callNumber')));
                        $st[] = array(
                            'id'            => $id,
                            'status'        => ($a)? 'available now' : 'not available',
                            'location'      => zTool::LocationString($n, $l),
                            'reserve'       => 'N',
                            'callnumber'    => empty($c)? '[unknown]' : $c,
                            'availability'  => $a  
                        );
                    }
                }
                $sts[] = $st;            
            }
        }
        $this->freeSession($zs);
        return $sts;
    }

    public function getHolding($id, array $patron = NULL)
    {
        $hld = array();
        if (empty($patron['cat_username'])) $zs = $this->takeSession();
            else $zs = $this->takeSession($patron['cat_username'], $patron['cat_password']);
        if ($zs->ready && $zs->SearchById(zTool::SerialUnEscape($id))) 
        {
            $hl = $zs->resultset->holdingsData;
            if (count($hl)) 
            {
                foreach ($hl as $h) 
                {            
                    $a = intval(current($h->xpath('circulationData/circRecord/availableNow')));                    
                    $r = strval(current($h->xpath('circulationData/circRecord/restrictions')));
                    $c = strval(current($h->xpath('callNumber')));
                    $b = strval(current($h->xpath('circulationData/circRecord/itemId')));
                    $n = strval(current($h->xpath('nucCode')));
                    $l = strval(current($h->xpath('localLocation')));
                    $hld[] = array(
                        'id'            => $id,
                        'nucCode'       => $n,
                        'localLocation' => $l,
                        'availability'  => $a,
                        'status'        => empty($r)? 'Unavailable' : $r,
                        'location'      => zTool::LocationString($n, $l),
                        'reserve'       => 'N',
                        'callnumber'    => empty($c)? '[unknown]' : $c,
                        'number'        => empty($b)? '[unknown]' : $b,
                        'barcode'       => empty($b)? '[unknown]' : $b,
                        'is_holdable'   => (empty($r) && $a)
                    );
                }
            }            
        }
        $this->freeSession($zs);
        return $hld;
    }

    public function getPurchaseHistory($id)
    {
        return array();
    }
    
    public function patronLogin($barcode, $password)
    {
        $u = array();        
        $zs = $this->takeSession($barcode, $password);
        if ($zs->ready)
        {
            if ($zs->SearchUserInfo())
            {
                $r = $zs->resultset->record;            
                $u['firstname'] = strval(current($r->xpath('tag[@value="102"]')));
                $u['surname']   = strval(current($r->xpath('tag[@value="103"]')));
                $u['lastname']  = strval(current($r->xpath('tag[@value="101"]')));
                $u['email']     = strval(current($r->xpath('tag[@value="122"]')));
                $u['college']   = strval(current($r->xpath('tag[@value="112"]')));
                $u['major']     = strval(current($r->xpath('tag[@value="107"]')));
            }
            $this->freeSession($zs);
        } else
        {
            if ($zs->error == 9998) return;
            $u['sysMessage'] = $zs->errormsg;
        }

        //if ( strtoupper( trim( $u['lastname'] )) != strtoupper( trim( getenv("sn") ) )) return;
        
        $u['id']           = trim($barcode);        
        $u['cat_username'] = trim($barcode);
        $u['cat_password'] = trim($password);        
        
        return $u;
    }

    public function getMyProfile($p)
    {
        return array(
            'firstname' => empty($p['firstname'])? '[unknown]' : $p['firstname'],
            'surname' => empty($p['surname'])? '[unknown]' : $p['surname'],
            'lastname'  => empty($p['lastname'])? '[unknown]' : $p['lastname'],
            'email'     => empty($p['email'])? '[unknown]' : $p['email'],
			'cat_username'=>empty($p['cat_username'])? '[unknown]' : $p['cat_username'],
 			'college'=>empty($p['college'])? '[unknown]' : $p['college'],
			'major'=>empty($p['major'])? '[unknown]' : $p['major'],
            'address1'  => null,
            'address2'  => null,
            'zip'       => null,
            'phone'     => null,
            'group'     => null
            );         
    }

    public function getMyFines($patron)
    {
        $f = array();
        if (! empty($patron['cat_username']))
        {
            $zs = $this->takeSession($patron['cat_username'], $patron['cat_password']);            
            if ($zs->ready && $zs->SearchItems())
            {
               foreach ($zs->resultset->records as $r)
               {
                  $i = zTool::StrInfo(strval(current($r->xpath('field[@id="999"]/subfield[@id="z"]'))));
                  if (empty($i)) continue;
                  $il = $i['duedate']->diff(new Datetime());                  
                  if ($il->invert == 0 && $il->days > 0)
                  {
                     $id = strval(current($r->xpath('field[@id="001"]')));
                     if (empty($id)) continue;
                            
                     $f[] = array(
                          "id"          => $id,
                          "title"       => strval(current($r->xpath("field[@id='200']/subfield[@id='a']"))),
                          "amount"      => null,
                          "checkout"    => $i['checkout']->format("d.m.Y"),
                          "fine"        => $il->days,
                          "balance"     => null,
                          "duedate"     => $i['duedate']->format("d.m.Y")
                     );
                  }                     
               }               
            }
            $this->freeSession($zs);
        }
        return $f;
    }

    public function getMyHolds($patron)
    {
        $hl = array();
        if (isset($patron['cat_username']) && !empty($patron['cat_username']))
        {
            $zs = $this->takeSession($patron['cat_username'], $patron['cat_password']);
            if ($zs->ready && $zs->SearchOrders())
            {
                foreach ($zs->resultset->records as $r)
                {
                    $id = strval(current($r->xpath('taskSpecificParameters/taskPackage/targetPart/itemRequest/record/field[@id="001"]')));
                    if (empty($id)) continue;                    
                    $rq   = strval(current($r->xpath('targetReference')));
                    $c    = strval(current($r->xpath('creationDateTime')));
                    $s    = strval(current($r->xpath('taskStatus')));
                    $rp   = strval(current($r->xpath('taskSpecificParameters/taskPackage/targetPart/statusOrErrorReport')));
                    $l    = strval(current($r->xpath('taskSpecificParameters/taskPackage/targetPart/itemRequest/record/field[@id="999"]/subfield[@id="b"]')));
                    $hl[] = array(            
                        'id'        => zTool::SerialEscape($id),
                        'reqnum'    => empty($rq)? '[unknown]' : $rq,
                        'create'    => empty($c)? null : DateTime::createFromFormat("YmdGis", $c)->format('d-M-Y'),
                        'expire'    => zTool::TaskStatus($s),
                        'title'     => strval(current($r->xpath('taskSpecificParameters/taskPackage/targetPart/itemRequest/record/field[@id="200"]/subfield[@id="a"]'))),
                        'location'  => $rp,
                        'available' => ($s == '1')
                    );
                }        
            }
            $this->freeSession($zs);
        }
        return $hl;
    }

    public function getMyTransactions($p)
    {
        $it = array();
        if (! empty($p['cat_username']))
        {
            $zs = new zSession($p['cat_username'], $p['cat_password']);
            if ($zs->ready && $zs->SearchItems()) 
            {
                foreach ($zs->resultset->records as $r)
                {
                    $id = strval(current($r->xpath('field[@id="001"]')));
                    if (empty($id)) continue;
                    $i = zTool::StrInfo(strval(current($r->xpath('field[@id="999"]/subfield[@id="z"]'))));                    
                    $it[] = array(            
                        'id'        => zTool::SerialEscape($id),
                        'duedate'   => (empty($i))? null : $i['duedate']->format("d.m.Y"),
                        'checkout'  => (empty($i))? null : $i['checkout']->format("d.m.Y"),
                        'message'   => (empty($i))? null : $i['note'],
                        'title'     => strval(current($r->xpath("field[@id='200']/subfield[@id='a']"))),
                        'item_id'   => null
                    );
                }        
            }
            $this->freeSession($zs);
        }
        return $it;
    }

    public function getPickUpLocations($patron = false, $holdDetails = null)
    {
        return array(
            array(
                'locationID' => 'Абн',
                'locationDisplay' => 'Научный абонемент'
            ),
            array(
                'locationID' => 'ЧЗ1',
                'locationDisplay' => 'Читальный зал 1'
            ),            
            array(
                'locationID' => 'ЧЗ3',
                'locationDisplay' => 'Читальный зал 3'
            )
        );
    }

    public function getDefaultPickUpLocation($patron = false, $holdDetails = null)
    {
        $locations = $this->getPickUpLocations($patron);
        return $locations[0]['locationID'];
    }

    public function getFunds()
    {
        return array();
    }

    public function getDepartments()
    {
        return array();
    }

    public function getInstructors()
    {
        return array();
    }

    public function getCourses()
    {
        return array();
    }

    public function getNewItems($page, $limit, $daysOld, $fundId = null)
    {
        return array();
    }

    public function findReserves($course, $inst, $dept)
    {
        return array();
    }

    public function cancelHolds($cancelDetails)
    {
        return array();
    }

    public function getCancelHoldDetails($holdDetails)
    {
        return $holdDetails['reqnum'];
    }

    public function renewMyItems($renewDetails)
    {
        return array();
    }

    public function getRenewDetails($checkOutDetails)
    {
        return $checkOutDetails['item_id'];
    }

    public function placeHold($h)
    {
        if (!empty($h['holdType']) && $h['holdType'] == '2')
        {
            return array(
                'success' => false,
                'sysMessage' => "Order failed: this order type not yet supported"
            );
        }
        if (empty($h['patron']['cat_username']))
        {
            return array(
                'success' => false,
                'sysMessage' => "Order failed: you should be logged in"
            );
        }
        $zs = $this->takeSession($h['patron']['cat_username'], $h['patron']['cat_password']);
        if (!$zs->ready)
        {
            return array(
                'success' => false,
                'sysMessage' => "Order failed: connection problem"
            );
        }
        if (!$zs->SearchById(zTool::SerialUnEscape($h['id']))) 
        {
            $this->freeSession($zs);
            return array(
                'success' => false,
                'sysMessage' => "Order failed: cannot find requested item"
            );            
        }
        if ($zs->OrderToHold( isset($h['nucCode'])? urlencode($h['nucCode']) : null,
                              isset($h['localLocation'])? urlencode($h['localLocation']) : null,
                              isset($h['placesInQueue'])? urlencode($h['placesInQueue']) : null,
                              isset($h['volumeIssue'])? urlencode($h['volumeIssue']) : null,
                              isset($h['requesterNote'])? urlencode($h['requesterNote']) : null,
                              isset($h['pickUpLocation'])? urlencode($h['pickUpLocation']) : null ))
        {
            $orderId = strval(current($zs->order->xpath('record/targetRefence')));
            $this->freeSession($zs);
            return array(
               'success' => true,
               'sysMessage' => $orderId
            );
        } else {
            $a = strval(current($zs->order->xpath('ESResponse/record/addInfo')));
            $d = strval(current($zs->order->xpath('ESResponse/record/condition')));            
            if (! empty($a)) $i = $a;
            else if (! empty($d)) $i = zTool::DiagString($d);
                 else $i = 'unspecified error';  
            $this->freeSession($zs);
            return array(
              'success' => false,
              'sysMessage' => sprintf("Order failed: %s", $i)
            );
        }        
        $this->freeSession($zs);
        return array(
              'success' => false,
              'sysMessage' => 'Order failed: unspecified error'
        );
    }    

    public function getConfig($function)
    {
        if ($function == 'Holds') {
            return array(
                'HMACKeys' => 'id:nucCode:localLocation',
                'extraHoldFields' => 'volumeIssue:requesterNote:pickUpLocation',
                'helpText' => 'Введите информацию относительно заказа.'
            );
        }
        return array();
    }
}

