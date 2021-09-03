<?php
namespace WHMCS\Module\Registrar\dotbx;
use WHMCS\Database\Capsule;
class ApiReseller {
    
    const API_URL = 'https://api.myclientserver.com/v1/shop/';
    
    public static function call($action, $postfields){
        
        $api_key    =  decrypt(ApiReseller::getauth('api-key')->value, $cc_encryption_hash);
        $reselleremail =  decrypt(ApiReseller::getauth('reseller-email')->value, $cc_encryption_hash);
        
        
        $return['success'] = FALSE;
        
        $session = curl_init();
        curl_setopt($session, CURLOPT_URL, self::API_URL . $action . '.php');
        curl_setopt($session, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($postfields));
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($session, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($session, CURLOPT_TIMEOUT, 100);
        curl_setopt($session, CURLOPT_HTTPHEADER, array(
            
            'api-key: '. $api_key .'',
            'reseller-email: '. $reselleremail .''
            
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
                        ->where('registrar', '=', 'dotbx')
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
         $res = ApiReseller::call('customer/GetDetails', array('useremail' => $params['email']));
        
        if($res['success']){
            
            $return['success'] = TRUE;
            $return['userid'] = $res['userid'];
        }else{
             $return = ApiReseller::AddCustomer($params);
        }
        
        return $return;
    }
    public static function AddCustomer($params) {
        
        $postfields['firstname']    =   trim($params['firstname']);               //    Required
        $postfields['lastname']     =   $params['lastname'];                //    Required
        $postfields['companyname']  =   $params['companyname'];             //    Required
        $postfields['useremail']    =   $params['email'];                   //    Required
        $postfields['address']      =   trim($params['address1'] . ' '.$params['address2']);     //    Required
        $postfields['city']         =   trim($params['city']);                     //    Required
        $postfields['statename']    =   $params['state'];                    //    Required
        $postfields['postalcode']   =   $params['postcode'];                //    Required
        $postfields['countryname']  =   $params['countryname'];             //    Required
        $postfields['phone']        =   $params['fullphonenumber'];
        
        
        $postfields['statename']    =   $params['fullstate'];               // Not Required
        $postfields['statecode']    =   $params['statecode'];               // Not Required
        $postfields['countrycode']  =   $params['countrycode'];             // Not Required
        $postfields['phonecc']      =   $params['phonecc'];                 // Not Required
        $postfields['phonenumber']  =   $params['phonenumber'];             // Not Required
        
        return ApiReseller::call('customer/AddCustomer', $postfields);
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
