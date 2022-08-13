<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
use WHMCS\Carbon;
use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use WHMCS\Module\Registrar\dotbx\ApiReseller;
use WHMCS\Database\Capsule;
use WHMCS\Domain\TopLevel\ImportItem;
use WHMCS\Domain\Registrar\Domain;


function dotbx_MetaData() {
    return array(
        'DisplayName' => 'DotBX',
        'APIVersion' => '1.0',
    );
}


function dotbx_GetConfigArray()
{
    return array(
        "Description" => array("Type" => "System","Value" => "DotBX - ICANN Accredited Domain Registrar"),
        "api-key"       => array("FriendlyName" => "API Key:","Type" => "text","Size" => "25","Default" => "", "Description" => "Enter your DotBX API Key here."),
        "reseller-email"=> array("FriendlyName" => "Reseller Email:","Type" => "text","Size" => "25","Default" => "", "Description" => "Enter your Reseller Email Id.")
        );
}


function dotbx_CheckAvailability($params) {

    array_walk($params["tlds"], function (&$value) {
        $value = substr($value, 1);
    });
      $postfields['searchTerm']   = $params['searchTerm'];
      $postfields['tlds']         = implode(",",$params["tlds"]);
    
      $res = ApiReseller::call('domain/checkdomainavailable', $postfields);
     
     if(!$res['success']){
         
         throw new Exception(json_encode($res['errors'][0]['message']));
     }else{
         
         $results = new ResultsList();
         
         try {
             
          foreach( $res['result'] as $domainName => $domainData ){
             
              $parts = explode(".", $domainName, 2);
             
             // Instantiate a new domain search result object
              $searchResult = new SearchResult($parts[0], $parts[1]);
    
                if($domainData['isAvailable']){
                    $status = SearchResult::STATUS_NOT_REGISTERED;
                }else{
                     $status = SearchResult::STATUS_TLD_NOT_SUPPORTED;
                }
        
                if($domainData['productkey'] == NULL){
                    $status = SearchResult::STATUS_TLD_NOT_SUPPORTED;
                }
           
             if($domainData['isPremiumName']){
                 if($params["premiumEnabled"] ){
                     
                     $searchResult->setPremiumDomain(true);
                     $searchResult->setPremiumCostPricing(
                            array(
                                'register'      => $domainData['price'],
                                'CurrencyCode'  => $domainData['currency'],
                            )
                        );                                
                 }else{
                      $status = SearchResult::STATUS_RESERVED;
                 }
              }
                 
          $searchResult->setStatus($status);
          $results->append($searchResult);
          }
                    return $results; 
         } catch (\Exception $e) {
            return array(
                'error' => $e->getMessage(),
            );
        }     
     }
        
}

function dotbx_DomainSuggestionOptions() {
    return array(
        'includeCCTlds' => array(
            'FriendlyName' => 'Include Country Level TLDs',
            'Type' => 'yesno',
            'Description' => 'Tick to enable',
        ),
    );
}  

function dotbx_GetDomainSuggestions($params){

    // array_walk($params["tldsToInclude"], function (&$value) {
    //     $value = $value;//substr($value, 1);
    // });
           
     $postfields['searchTerm']   = $params["punyCodeSearchTerm"] ?: $params["searchTerm"];
     $postfields['tlds']         = implode(",",$params["tldsToInclude"]);
     
    try {
        
        $results = new ResultsList();
        $res = ApiReseller::call('domain/checkdomainavailable', $postfields);
        
        foreach( $res['result'] as $domainName => $domainData ) {
            $parts = explode(".", $domainName, 2);
            
             // Instantiate a new domain search result object
            $searchResult = new SearchResult($parts[0], $parts[1]);
            
                 if($domainData['isAvailable']) {
                     $status = SearchResult::STATUS_NOT_REGISTERED;
                 }else{
                     $status = SearchResult::STATUS_REGISTERED;
                 }
            
            if($domainData['premiumEnabled']) {
                if( $params["isPremiumName"] ) {
                    
                    $searchResult->setPremiumDomain(true);
                    $searchResult->setPremiumCostPricing(
                        array(
                            'register'      => $domainData['price'],
                            'CurrencyCode'  => $domainData['currency'],
                        )
                );
                    
                }else{
                    $status = SearchResult::STATUS_RESERVED;
                }
                
            }    
                  $searchResult->setStatus($status);
                 
            $results->append($searchResult);
        }
              
    return $results;
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }  
}
function dotbx_RegisterDomain($params) {
    
    $res = ApiReseller::GetCustomerID($params);
    
    if($res['success']){

         // submitted nameserver values
        $nameserver1 = $params['ns1'];
        $nameserver2 = $params['ns2'];
        $nameserver3 = $params['ns3'];
        $nameserver4 = $params['ns4'];
        $nameserver5 = $params['ns5'];
            
        $postfields['userid']           =   $res['userid'];
        $postfields['domainname']       =   $params['domainname'];
        $postfields['regperiod']        =   $params['regperiod'];
        $postfields['privacy']          =   (bool) $params['idprotection'];       
        
        if ($nameserver1) {
            $postfields['nameServer'][] = $params['ns1'];
        }
        if ($nameserver2) {
            $postfields['nameServer'][] = $params['ns2'];
        }
        if ($nameserver3) {
            $postfields['nameServer'][] = $params['ns3'];
        }
        if ($nameserver4) {
            $postfields['nameServer'][] = $params['ns4'];
        }
        if ($nameserver5) {
            $postfields['nameServer'][] = $params['ns5'];
        }
        
         $response = ApiReseller::call('domain/register', $postfields);
         
         if(!$response['success']){
             return  array('error' =>  ApiReseller::error($response['errors']));
         }
         
    }else{
        return  array('error' =>  ApiReseller::error($res['errors']));
    }
    
}

function dotbx_TransferDomain($params) {
    
    if(!empty($params['transfersecret'])) {
      
     $res = ApiReseller::GetCustomerID($params);
    
    if($res['success']) {
        
        $postfields['userid']           =   $res['userid'];
        $postfields['domainname']       =   $params['domainname'];
        $postfields['privacy']          =   ($params['idprotection'] ? "true" : "false");
        $postfields['authCode']         =   trim($params['transfersecret']);

        $response = ApiReseller::call('domain/transfer', $postfields);
        
        if(!$response['success']) {
            return  array('error' =>  ApiReseller::error($response['errors']));
        } 
    }else{
        return  array('error' =>  ApiReseller::error($res['errors']));
    }
    }else{
        return  array('error' =>  'EPP Code Can\'t Blank');
    }
}

function dotbx_GetDomainInformation($params){
    
    $postfields['domainname'] = $params['domainname'];
    
    $res = ApiReseller::call('domain/view/details', $postfields);
    
   if($res['success']) {
            
            $expirydate = date("Y-m-d",strtotime($res['expires']));
           $nameservers = array();
            $x=0;
            foreach($res['nameServers'] as $key){
                $x++;
                $ns="ns".$x;
                $nameservers["ns".$x]   = $key;
            }          
     
       return (new Domain)
        ->setDomain($res['domainname'])
        ->setNameservers($nameservers)
        ->setRegistrationStatus($res['status'])
        ->setTransferLock($res['locked'])
        ->setExpiryDate(Carbon::createFromFormat('Y-m-d', $expirydate)); // $response['expirydate'] = YYYY-MM-DD
      
   }else{
       return array( 'error' => $res['errors'][0]['message'] );
   }
    
}

function dotbx_GetNameservers($params){
    
    $postfields['domainname'] = $params['domainname'];
    
    $res = ApiReseller::call('domain/view/details', $postfields);
    
    // return array('error' =>  json_encode($response));
    
   if($res['success']) {
            
           $nameservers = array();
            $x=0;
            foreach($res['nameServer'] as $key){
                $x++;
                $ns="ns".$x;
                $nameservers["ns".$x]   = $key;
            }          
     
       return $nameservers;
      
   }else{
       return array( 'error' => $res['errors'][0]['message'] );
   }
}
function dotbx_SaveNameservers($params){
    
    $domain = ApiReseller::getDomain($params['domainid']);
    
    if($domain->status == 'Active'){
        
    // submitted nameserver values
    $nameserver1 = $params['ns1'];
    $nameserver2 = $params['ns2'];
    $nameserver3 = $params['ns3'];
    $nameserver4 = $params['ns4'];
    $nameserver5 = $params['ns5'];
    
        $postfields['domainname'] = $params['domainname'];
        if ($nameserver1) {
            $postfields['nameServer'][] = $nameserver1;
        }
        if ($nameserver2) {
            $postfields['nameServer'][] = $nameserver2;
        }
        if ($nameserver3) {
            $postfields['nameServer'][] = $nameserver3;
        }
        if ($nameserver4) {
            $postfields['nameServer'][] = $nameserver4;
        }
        if ($nameserver5) {
            $postfields['nameServer'][] = $nameserver5;
        }    
        
        // return  array('error' =>  json_encode($postfields));
        $res = ApiReseller::call('domain/save/details', $postfields);
        
        if(!$res['success']){
            
            return  array('error' =>  ApiReseller::error($res['errors']));
             
        }
    }else{
            
        // domain Not Active Whmcs Softwere
        return array('error' => 'Domain is not Active.');
    }
    
}
function dotbx_GetEPPCode($params) {
    
    $postfields['domainname'] = $params['domainname'];
    
    $res = ApiReseller::call('domain/view/details', $postfields);
    
   if($res['success']) {
       
            return array('eppcode' => $res['authCode']);
      
   }else{
       return array( 'error' => $res['errors'][0]['message'] );
   }
    
    
}

function dotbx_GetRegistrarLock($params){
    
    $postfields['domainname'] = $params['domainname'];
    
    $res = ApiReseller::call('domain/view/details', $postfields);
    
   if($res['success']) {
            
          $transferlock = 'Unlocked';
        if($response['locked']) {
            
            $transferlock = 'locked';
        }
        return $transferlock;
   }else{
       return array( 'error' => $res['errors'][0]['message'] );
   }
}
function dotbx_SaveRegistrarLock($params){
    
    $domain = ApiReseller::getDomain($params['domainid']);
    
    if($domain->status == 'Active'){
 
        $postfields['isThiefProtected'] =  false;
        
        $postfields['domainname'] = $params['domainname'];
        if($params['lockenabled'] == 'locked'){
            
            $postfields['isThiefProtected'] =  true;
        }
        
        $res = ApiReseller::call('domain/save/details', $postfields);
        
        if(!$res['success']){
            
            return  array('error' =>  ApiReseller::error($res['errors']));
             
        } 
    }else{
            
        // domain Not Active Whmcs Softwere
        return array('error' => 'Domain is not Active.');
    }
} 
function dotbx_sync($params) {
    
    $postfields['domainname'] = $params['domainname'];
    
     $res = ApiReseller::call('domain/view/details', $postfields);
     
     if($res['success']) {
        $isActive = FALSE;
        $isexpired = FALSE;
        $istransferrOUT = FALSE;
        if($response['status'] == "Active"){ $isActive = TRUE;}
        if($response['status'] == "Expired"){ $isexpired = TRUE;}
        if($response['status'] == "TransferOut"){ $istransferrOUT = TRUE;}         

        return array(
            'expirydate' => date("Y-m-d", strtotime($res['expires'])), // Format: YYYY-MM-DD
            'active' => (bool) $isActive, // Return true if the domain is active
            'expired' => (bool) $isexpired, // Return true if the domain has expired
            'transferredAway' => (bool) $istransferrOUT, // Return true if the domain is transferred out
        );         
     }else{
          return  array('error' =>  ApiReseller::error($res['errors']));
     }
}

function dotbx_GetTldPricing(array $params){
    
    $postfields = array();
    $response = ApiReseller::call('tldsync', $postfields);
    
    if($response['success']) {
        
        $results = new ResultsList();
        try{
            
            foreach ($response['result'] as $extension) {
                // All the set methods can be chained and utilised together.

                $item = (new ImportItem)
                    ->setExtension($extension['tldname'])
                    ->setMinYears('1')
                    ->setMaxYears('10')
                    ->setRegisterPrice($extension['registrationPrice'])
                    ->setRenewPrice($extension['renewalPrice'])
                    ->setTransferPrice($extension['transferPrice'])
                    ->setRedemptionFeePrice($extension['redemption_grace_period_fee'])
                    ->setEppRequired(TRUE)
                    ->setCurrency($extension['currencyCode']);

                 $results[] = $item;                
            }
          return $results;
        } catch (\Exception $e) {
            return array( 'error' => $e->getMessage() );
        }
    }else{
         return  array('error' =>  ApiReseller::error($response['errors']));
    }
}
