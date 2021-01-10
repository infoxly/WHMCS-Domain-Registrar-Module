<?php
namespace WHMCS\Module\Registrar\recoverhosting;
use WHMCS\Database\Capsule;
class ApiReseller {
    
    const API_URL = 'https://beta.myclientserver.com/shop/v1/';
    
    public static function call($action, $callmethod, $postfields){
        
        $api_key    =  decrypt(ApiReseller::getauth('api-key')->value, $cc_encryption_hash);
        $reselleremail =  decrypt(ApiReseller::getauth('reseller-email')->value, $cc_encryption_hash);
        
        
        $return = array(
            'success'   =>  FALSE,
            'errors'    =>  array(),
            'messages'  =>  array(),
            'result'    =>  array()
            );
            
        $session = curl_init();
        curl_setopt($session, CURLOPT_URL, self::API_URL . $action . '.php');
        curl_setopt($session, CURLOPT_CUSTOMREQUEST, $callmethod);
        curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($postfields));
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($session, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($session, CURLOPT_TIMEOUT, 100);
        curl_setopt($session, CURLOPT_HTTPHEADER, array(
            
            'X-Auth-Key: '. $api_key .'',
            'X-Auth-email: '. $reselleremail .''
            
            )); 
            
        $response = curl_exec($session);
        
        if (curl_errno($session)) {
            $return['errors'][] =   array('message' => 'curl error: ' . curl_errno($session) . " - " . curl_error($session));
        }    
        
        curl_close($session);
        
        $results = json_decode($response, true);
        
        if ($results === null && json_last_error() !== JSON_ERROR_NONE) {
            $return['errors'][] =   array('message' => 'Bad response received from API');
        }else{
            $return = $results;
        }
        
        return $return;            
            
    }

    public static function getauth($setting) {
        return Capsule::table('tblregistrars')
                        ->where('registrar', '=', 'recoverhosting')
                        ->where('setting', '=', $setting)
                        ->select('value')
                        ->first();
    }
    
    public static function getDomain($domainid) {
        return Capsule::table('tbldomains')
                        ->where('id', '=', $domainid)
                        ->first();
    }
    
    public static function GetCustomerID ($params) {
        
        $return['success'] = FALSE;
         $res = ApiReseller::call('customer/GetDetails','GET', array('useremail' =>$params['email']));
        
        if($res['success']){
            
            $return['success'] = TRUE;
            $return['customerid'] = $res['customerid'];
        }else{
             $return = ApiReseller::AddCustomer($params);
        }
        
        return $return;
    }
    public static function AddCustomer($params) {
        
        $postfields['firstname']    =   $params['firstname'];
        $postfields['lastname']     =   $params['lastname'];
        $postfields['companyname']  =   $params['companyname'];
        $postfields['useremail']    =   $params['email'];
        $postfields['address']      =   trim($params['address1'] . ' '.$params['address2']);
        $postfields['city']         =   $params['city'];
        $postfields['state']        =   $params['state'];
        $postfields['fullstate']    =   $params['fullstate'];
        $postfields['statecode']    =   $params['statecode'];
        $postfields['postcode']     =   $params['postcode'];
        $postfields['countrycode']  =   $params['countrycode'];
        $postfields['countryname']  =   $params['countryname'];
        $postfields['phonecc']      =   $params['phonecc'];
        $postfields['phonenumber']  =   $params['phonenumber'];
        $postfields['mobileno']     =   $params['fullphonenumber'];
        
        return ApiReseller::call('customer/AddCustomer','POST', $postfields);
    } 
    
    public static function error($error){
        $x;
        $return = array();
        foreach ($error as $key => $value){
            
            $return[] = $value['message'];
          $x++;  
        }
        return $x. " Message ==> ( " . implode(", ", $return) ." )";
    }
}