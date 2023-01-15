<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
use WHMCS\Module\Registrar\infoxly\ApiReseller;
use WHMCS\Database\Capsule;

function infoxly_MetaData() {
    return array(
        'DisplayName' => '#INFOXLY',
        'APIVersion' => '1.0',
    );
}

function infoxly_GetConfigArray()
{
    return array(
        "Description"   => array("Type" => "System","Value" => ""),
        "auth-userid"   => array("FriendlyName" => "ResellerID:","Type" => "text","Size" => "25","Default" => "", "Description" => "Enter your ResellerID."),
        "api-key"       => array("FriendlyName" => "API Key:","Type" => "text","Size" => "25","Default" => "", "Description" => "Enter your INFOXLY API Key here.")
        );
}

function infoxly_RegisterDomain($params){
    
    $res = ApiReseller::GetCustomerID($params);
    
    if($res['success']){
        
        $customerid = $res['customerid'];
         // submitted nameserver values
        $nameserver1 = $params['ns1'];
        $nameserver2 = $params['ns2'];
        $nameserver3 = $params['ns3'];
        $nameserver4 = $params['ns4'];
        $nameserver5 = $params['ns5'];

        if ($nameserver1) {
            $nameServer[] = $params['ns1'];
        }
        if ($nameserver2) {
            $nameServer[] = $params['ns2'];
        }
        if ($nameserver3) {
            $nameServer[] = $params['ns3'];
        }
        if ($nameserver4) {
            $nameServer[] = $params['ns4'];
        }
        if ($nameserver5) {
            $nameServer[] = $params['ns5'];
        } 
        $contectType    =   "Contact";
        $contect = ApiReseller::searchcontact($params, $customerid, $contectType);
        
        if($contect["success"])
        {   
            
            $postfields['customerid']       =   $customerid;
            $postfields['domainname']       =   $params['domainname'];
            $postfields['years']            =   $params['regperiod'];
            $postfields['privacy']          =   (bool) $params['idprotection'];   
            $postfields["nameServer"]       =   ApiReseller::implodeToString($nameServer);
            
            $postfields["registrant_handle"]=   $contect["contactid"];
            $postfields["admin_handle"]     =   $contect["contactid"];
            $postfields["tech_handle"]      =   $contect["contactid"];
            $postfields["billing_handle"]   =   $contect["contactid"];
            
            $response = ApiReseller::call('domains/register', 'POST', $postfields );
            
            if(!$response['success']){
                 return  array('error' =>  ApiReseller::error($response['errors']));
            }
            // return  array('error' =>  json_encode($response));
        }else{
            return  array('error' =>  ApiReseller::error($contect['errors']));
        }       
    
    }else{
        return  array('error' =>  ApiReseller::error($res['errors']));
    }
}

function infoxly_TransferDomain($params) {
    
    if(!empty($params['transfersecret'])) {
      
     $res = ApiReseller::GetCustomerID($params);
    
    if($res['success']) {
        
        $customerid = $res['customerid'];
        $contectType    =   "Contact";
        $contect = ApiReseller::searchcontact($params, $customerid, $contectType);
        
        if($contect["success"])
        {
            $postfields['customerid']       =   $customerid;
            $postfields['domainname']       =   $params['domainname'];
            $postfields['password']         =   $params['transfersecret'];
            $postfields['privacy']          =   (bool) $params['idprotection'];   
            
            $postfields["registrant_handle"]=   $contect["contactid"];
            $postfields["admin_handle"]     =   $contect["contactid"];
            $postfields["tech_handle"]      =   $contect["contactid"];
            $postfields["billing_handle"]   =   $contect["contactid"];
        
            $response = ApiReseller::call('domains/transfer', 'POST', $postfields );
            
            if(!$response['success']){
                 return  array('error' =>  ApiReseller::error($response['errors']));
            }
        }else{
            return  array('error' =>  ApiReseller::error($contect['errors']));
        }        
       
    }else{
        return  array('error' =>  ApiReseller::error($res['errors']));
    }
    }else{
        return  array('error' =>  'EPP Code Can\'t Blank');
    }
}

function infoxly_RenewDomain($params)
{
    $domain = ApiReseller::getDomain($params['domainid']);
    
        if($domain->status == 'Active'){
            
          $order =  ApiReseller::Getorderid($params);
           
          if($order['success']){
              

            $postfields['orderid']  = $order["orderid"];
            $postfields["years"]    = $params["regperiod"];
            $res = ApiReseller::call('domains/renew', 'POST', $postfields);
            
            if(!$res['success'])
            {
              if(isset($res['errors'])){
                  return  array('error' =>  ApiReseller::error($res['errors']));
              }else{
                  return  array('error' =>  'Nameserver update Failed.'. json_encode($res) );
              }
              
            }
          }else{
              return  array('error' =>  ApiReseller::error($order['errors']));
          }
    }else{
            // domain Not Active Whmcs Softwere
        return array('error' => 'Domain is not Active.');

    }
}
function infoxly_GetNameservers($params)
{
   $order =  ApiReseller::Getorderid($params);
   
  if($order['success']){
       
      $orderid =   $order['orderid'];
       
      $res = ApiReseller::call('domains/details', 'POST', array('orderid' => $orderid ));
      if($res['success'])
      {
           
          if (is_array($res['nameServer']) || is_object($res['nameServer']))
          {
               
              $nameservers = array();
               
                  $x=0;
                    foreach($res['nameServer'] as $key){
                        $x++;
                        $ns="ns".$x;
                        $nameservers["ns".$x]   = $key;
                    }
                    
                return $nameservers;
          }else{
               
               
              $error['errors'][] = array('code' => '', 'message' =>  'currently not set nameservers.');
            //   $error['errors'][] = array('code' => '', 'message' =>  'if you placed domain Transfer Request please wait display nameserver after complete Transfer.');
               
                return  array( 'error' =>  ApiReseller::error($error['errors']) );
          }
           
           
      }else{
          return  array('error' =>  ApiReseller::error($order['errors']));
      }
       
  }else{
      return  array('error' =>  ApiReseller::error($order['errors']));
  }
}

function infoxly_SaveNameservers($params)
{
    $domain = ApiReseller::getDomain($params['domainid']);
    
        if($domain->status == 'Active'){
            
          $order =  ApiReseller::Getorderid($params);
           
          if($order['success']){
              
              
               
                // submitted nameserver values
                $nameserver1 = $params['ns1'];
                $nameserver2 = $params['ns2'];
                $nameserver3 = $params['ns3'];
                $nameserver4 = $params['ns4'];
                $nameserver5 = $params['ns5'];
                
                    if ($nameserver1) {
                        $nameServer[] = $nameserver1;
                    }
                    if ($nameserver2) {
                        $nameServer[] = $nameserver2;
                    }
                    if ($nameserver3) {
                        $nameServer[] = $nameserver3;
                    }
                    if ($nameserver4) {
                        $nameServer[] = $nameserver4;
                    }
                    if ($nameserver5) {
                        $nameServer[] = $nameserver5;
                    }
                $postfields['orderid'] = $order["orderid"];
                $postfields['nameServer']= ApiReseller::implodeToString($nameServer);
               
               $res = ApiReseller::call('domains/details', 'PUT', $postfields);
               
              if(!$res['success'])
              {
                  if(isset($res['errors'])){
                      return  array('error' =>  ApiReseller::error($res['errors']));
                  }else{
                      return  array('error' =>  'Nameserver update Failed.'. json_encode($res) );
                  }
                  
              }
          }else{
              return  array('error' =>  ApiReseller::error($order['errors']));
          }
    }else{
            // domain Not Active Whmcs Softwere
        return array('error' => 'Domain is not Active.');

    }
}

function infoxly_GetEPPCode($params)
{
  $order =  ApiReseller::Getorderid($params);
   
  if($order['success']){
       
      $orderid =   $order['orderid'];
       
      $res = ApiReseller::call('domains/details', 'POST', array('orderid' => $orderid ));
       
      if($res['success'])
      {
           
          return array('eppcode' => $res['password']);
           
           
      }else{
          return  array('error' =>  ApiReseller::error($order['errors']));
      }
       
  }else{
      return  array('error' =>  ApiReseller::error($order['errors']));
  }
}

function infoxly_GetRegistrarLock($params)
{
  $order =  ApiReseller::Getorderid($params);
   
  if($order['success']){
       
      $orderid =   $order['orderid'];
       
      $res = ApiReseller::call('domains/details', 'POST', array('orderid' => $orderid ));
       
      if($res['success'])
      {
           
          if($res['transferlock']){
              return 'locked';
          }else{
              return 'unlocked';
          }
           
           
      }else{
          return  array('error' =>  ApiReseller::error($order['errors']));
      }
       
  }else{
      return  array('error' =>  ApiReseller::error($order['errors']));
  }
}


function infoxly_SaveRegistrarLock($params){
    
    $domain = ApiReseller::getDomain($params['domainid']);
    
    if($domain->status == 'Active'){
        
        $order =  ApiReseller::Getorderid($params);
        if($order['success']){
            
            $postfields['orderid'] = $order["orderid"];
            $postfields['transferlock'] =  false;
            
            if($params['lockenabled'] == 'locked'){
                
                $postfields['transferlock'] =  true;
            }
            
            $res = ApiReseller::call('domains/details', 'PUT', $postfields);
            if(!$res['success']){
                return  array('error' =>  ApiReseller::error($res['errors']));
            }
        }else{
            return  array('error' =>  ApiReseller::error($order['errors']));
        }
    }else{
            // domain Not Active Whmcs Softwere
        return array('error' => 'Domain is not Active.');

    }
}

function infoxly_RegisterNameserver($params){
    
    $domain = ApiReseller::getDomain($params['domainid']);
    
    if($domain->status == 'Active'){
        
        $order =  ApiReseller::Getorderid($params);
        if($order['success']){
            
            $postfields["orderid"]      =   $order['orderid'];
            $postfields["hostname"]     =   $params["nameserver"];
            $postfields["ip"]           =   $params["ipaddress"];
            
            
            $res = ApiReseller::call('domains/details', 'PATCH', $postfields);
            if(!$res['success']){
                return  array('error' =>  ApiReseller::error($res['errors']));
            }else{
                return array( "success" => "success");
            }
            
        }else{
            return  array('error' =>  ApiReseller::error($order['errors']));
        }
    }else{
        return array('error' => 'Domain is not Active.');
    }
}
function infoxly_ModifyNameserver($params){
    
    $domain = ApiReseller::getDomain($params['domainid']);
    
    if($domain->status == 'Active'){
        
        $order =  ApiReseller::Getorderid($params);
        if($order['success']){
            
            $postfields["orderid"]      =   $order['orderid'];
            $postfields["hostname"]     =   $params["nameserver"];
            $postfields["ip"]           =   $params["newipaddress"];
            
            $res = ApiReseller::call('domains/details', 'PUT', $postfields);
            if(!$res['success']){
                return  array('error' =>  ApiReseller::error($res['errors']));
            }else{
                return array( "success" => "success");
            }
            
        }else{
            return  array('error' =>  ApiReseller::error($order['errors']));
        }
    }else{
        return array('error' => 'Domain is not Active.');
    }
}
function infoxly_DeleteNameserver($params){
    
    $domain = ApiReseller::getDomain($params['domainid']);
    
    if($domain->status == 'Active'){
        
        $order =  ApiReseller::Getorderid($params);
        if($order['success']){
            
            $postfields["orderid"]      =   $order['orderid'];
            $postfields["hostname"]     =   $params["nameserver"];
            
            $res = ApiReseller::call('domains/details', 'DELETE', $postfields);
            if(!$res['success']){
                return  array('error' =>  ApiReseller::error($res['errors']));
            }else{
                return array( "success" => "success");
            }
            
        }else{
            return  array('error' =>  ApiReseller::error($order['errors']));
        }
    }else{
        return array('error' => 'Domain is not Active.');
    }
}

function infoxly_sync_expiry_date($params)
{
    $domain = ApiReseller::getDomain($params['domainid']);
    
        if($domain->status == 'Active'){
            
          $order =  ApiReseller::Getorderid($params);
           
          if($order['success']){
              
              $orderid =   $order['orderid'];
               
              $res = ApiReseller::call('domains/details', 'POST', array('orderid' => $orderid ));
               
              if($res['success'])
              {
                  //update domain expiry table data
                  Capsule::table('tbldomains')
                        ->where('id', '=', $params['domainid'])
                        ->where('userid', '=', $params['userid'])
                        ->update([ 'expirydate'   =>   $res['expires'] ]);
                        
                  header("Refresh:0; url=clientsdomains.php?userid={$params['userid']}&domainid=".$params['domainid']);
                   
              }else{
                  return  array('error' =>  ApiReseller::error($order['errors']));
              }

              
          }else{
              return  array('error' =>  ApiReseller::error($order['errors']));
          }
    }else{
            // domain Not Active Whmcs Softwere
        return array('error' => 'Domain is not Active.');

    }
}

function infoxly_AdminCustomButtonArray() {
    $buttonarray = array(
	 "Sync expiry date" => "sync_expiry_date",
	);
	return $buttonarray;
}
