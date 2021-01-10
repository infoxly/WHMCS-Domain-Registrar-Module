<?php
use WHMCS\Module\Registrar\recoverhosting\ApiReseller;
use WHMCS\Database\Capsule;

    function recoverhosting_MetaData() {
        return array(
            'DisplayName' => 'RecoverHosting Registrar Module for WHMCS (Beta)',
            'APIVersion' => '1.0',
        );
    }
    function recoverhosting_GetConfigArray()
    {
        return array(
            "api-key"       => array("FriendlyName" => "API Key:","Type" => "text","Size" => "25","Default" => "", "Description" => "Enter your RecoverHosting API Key here."),
            "reseller-email"=> array("FriendlyName" => "Reseller Email:","Type" => "text","Size" => "25","Default" => "", "Description" => "Enter your Reseller Email Id.")
            );
    }
    

    function recoverhosting_RegisterDomain($params) {
        
         $res = ApiReseller::GetCustomerID($params);
        
        if($res['success']) {
            
            $postfields['customerid']       =   $res['customerid'];
            $postfields['domainname']       =   $params['domainname'];
            $postfields['regperiod']        =   $params['regperiod'];
            $postfields['purchase-privacy'] =   ($params['idprotection'] ? "true" : "false");
            
             // submitted nameserver values
            $nameserver1 = $params['ns1'];
            $nameserver2 = $params['ns2'];
            $nameserver3 = $params['ns3'];
            $nameserver4 = $params['ns4'];
            $nameserver5 = $params['ns5'];
            
            if ($nameserver1) {
                $postfields['nameserver']["ns1"] = $params['ns1'];
            }
            if ($nameserver2) {
                $postfields['nameserver']["ns2"] = $params['ns2'];
            }
            if ($nameserver3) {
                $postfields['nameserver']["ns3"] = $params['ns3'];
            }
            if ($nameserver4) {
                $postfields['nameserver']["ns4"] = $params['ns4'];
            }
            if ($nameserver5) {
                $postfields['nameserver']["ns5"] = $params['ns5'];
            }
            
            $response = ApiReseller::call('domain/register', 'POST', $postfields);
            
            if(!$response['success']) {
                return  array('error' =>  ApiReseller::error($response['errors']));
            } 
        }else{
            return  array('error' =>  ApiReseller::error($res['errors']));
        }
    }

    function recoverhosting_TransferDomain($params) {
        
         $res = ApiReseller::GetCustomerID($params);
        
        if($res['success']) {
            
            $postfields['customerid']       =   $res['customerid'];
            $postfields['domainname']       =   $params['domainname'];
            $postfields['regperiod']        =   $params['regperiod'];
            $postfields['purchase-privacy'] =   ($params['idprotection'] ? "true" : "false");
            
            $postfields['transfersecret']  =  trim($params['transfersecret']);
            
             // submitted nameserver values
            $nameserver1 = $params['ns1'];
            $nameserver2 = $params['ns2'];
            $nameserver3 = $params['ns3'];
            $nameserver4 = $params['ns4'];
            $nameserver5 = $params['ns5'];
            
            if ($nameserver1) {
                $postfields['nameserver']["ns1"] = $params['ns1'];
            }
            if ($nameserver2) {
                $postfields['nameserver']["ns2"] = $params['ns2'];
            }
            if ($nameserver3) {
                $postfields['nameserver']["ns3"] = $params['ns3'];
            }
            if ($nameserver4) {
                $postfields['nameserver']["ns4"] = $params['ns4'];
            }
            if ($nameserver5) {
                $postfields['nameserver']["ns5"] = $params['ns5'];
            }
            
            $response = ApiReseller::call('domain/transfer', 'POST', $postfields);
            if(!$response['success']) {
                return  array('error' =>  ApiReseller::error($response['errors']));
            } 
        }else{
            return  array('error' =>  ApiReseller::error($res['errors']));
        }
    }
    
    function recoverhosting_GetNameservers($params)
    {
        $domain = ApiReseller::getDomain($params['domainid']);
        
        if($domain->status == 'Active') {
            
            $postfields['domainname'] = $params['domainname'];
            
             $response = ApiReseller::call('domain/view/nameserver','GET', $postfields);
            
             if($response['success']){
                
                 $return = array();
                $x=0;
                foreach($response['nameservers'] as $key){
                    $x++;
                    $ns="ns".$x;
                    $return["ns".$x]   = $key;
                }   
                
            return $return;
                 
            }else{
                return  array('error' =>  ApiReseller::error($response['errors']));
            }
        }else{
            
            // domain Not Active Whmcs Softwere
            return array('error' => 'Domain is not Active.');
        }
    }
    
    function recoverhosting_SaveNameservers($params) {
        $domain = ApiReseller::getDomain($params['domainid']);
        
        if($domain->status == 'Active') {
            
            $postfields['domainname'] = $params['domainname'];
            
            // submitted nameserver values
            $nameserver1 = $params['ns1'];
            $nameserver2 = $params['ns2'];
            $nameserver3 = $params['ns3'];
            $nameserver4 = $params['ns4'];
            $nameserver5 = $params['ns5'];
            
            if ($nameserver1) {
                $postfields['nameserver']["ns1"] = $nameserver1;
            }
            if ($nameserver2) {
                $postfields['nameserver']["ns2"] = $nameserver2;
            }
            if ($nameserver3) {
                $postfields['nameserver']["ns3"] = $nameserver3;
            }
            if ($nameserver4) {
                $postfields['nameserver']["ns4"] = $nameserver4;
            }
            if ($nameserver5) {
                $postfields['nameserver']["ns5"] = $nameserver5;
            }                
             $response = ApiReseller::call('domain/update/nameserver','POST', $postfields);
        
              if(!$response['success']){
                    
                    return  array('error' =>  ApiReseller::error($response['errors']));
                     
                }
        }else{
            
            // domain Not Active Whmcs Softwere
            return array('error' => 'Domain is not Active.');
        }
    }
    
    function recoverhosting_GetRegistrarLock($params) {
        $domain = ApiReseller::getDomain($params['domainid']);
        
        if($domain->status == 'Active') {
            
            $postfields['domainname'] = $params['domainname'];
            
             $response = ApiReseller::call('domain/view/TheftProtection','GET', $postfields);
            
             if($response['success']){
                 
                 if($response['isThiefProtected']) {
                     
                     return 'locked';
                 }
             }else{
                return  array('error' =>  ApiReseller::error($response['errors']));
            }
        }else{
            
            // domain Not Active Whmcs Softwere
            return array('error' => 'Domain is not Active.');
        }
    }
    
    function recoverhosting_SaveRegistrarLock($params) {
        $domain = ApiReseller::getDomain($params['domainid']);
        
        if($domain->status == 'Active') {
            
            $postfields['domainname'] = $params['domainname'];
            $postfields['isThiefProtected'] = "false";
            
            if($params['lockenabled'] == "locked") {
                
                $postfields['isThiefProtected'] = "true";
                
            }
             $response = ApiReseller::call('domain/update/TheftProtection','POST', $postfields);
            
              if(!$response['success']){
                    
                    return  array('error' =>  ApiReseller::error($response['errors']));
                     
                }
        }else{
            
            // domain Not Active Whmcs Softwere
            return array('error' => 'Domain is not Active.');
        }
    }
    
    function recoverhosting_GetEPPCode($params) {
        $domain = ApiReseller::getDomain($params['domainid']);
        
        if($domain->status == 'Active') {
            
            $postfields['domainname'] = $params['domainname'];
            
              $response = ApiReseller::call('domain/view/TransferCode','GET', $postfields);
                    
              if($response['success']){
                    
                    return array('eppcode' =>  $response['TransferCode']);
                     
                }else{
                     return  array('error' =>  ApiReseller::error($response['errors']));
                }
        }else{
            
            // domain Not Active Whmcs Softwere
            return array('error' => 'Domain is not Active.');
        }
    }