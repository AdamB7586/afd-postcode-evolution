<?php
/**
 * The AFD class is made to be used with the AFD Postcode Evolution program to get the users postcode information
 * @author Adam Binnersley <abinnersley@gmail.com>
 */
namespace AFD;

class AFD{
    private static $AFD_HOST = 'http://localhost';
    private static $AFD_PORT = 81;

    public $address1, $address2, $address3, $town, $county, $latitude, $longitude;
    
    /**
     * Sets the host where the AFD Postcode Evolution is installed
     * @param string $host This should be a valid URL
     * @return void
     */
    public function setAFDHost($host){
        self::$AFD_HOST = $host;
    }
    
    /**
     * Sets the port number to look for the AFD Postcode data
     * @param int $port This should be the Port number that the Postcode evolution is installed on
     * @return void
     */
    public function setAFDPort($port){
        if(is_numeric($port)){
            self::$AFD_PORT = $port;
        }
    }
    
    /**
     * Returns a list of all of the addresses with the given postcode
     * @param string $postcode Should be the postcode you wish to find the addresses for
     * @return array|boolean Returns a list of the addresses or returns false if program is not active
     */
    public function findAddresses($postcode){
        if($this->programActive()){
            $xml = $this->get_data(self::$AFD_HOST.':'.self::$AFD_PORT.'/addresslist.pce?postcode='.$postcode);
            if($xml->AddressListItem[0]->Address != 'Error: Postcode Not Found'){
                for($i = 0; $i < count($xml->AddressListItem); $i++){
                    $addresses[$i]['address'] = (string)trim(str_replace($postcode, '', $xml->AddressListItem[$i]->Address));
                    $addresses[$i]['key'] = (string)$xml->AddressListItem[$i]->PostKey;
                }
                return $addresses;
            }
        }
        return false;
    }
    
    /**
     * Returns the details for any given postcode
     * @param string $postcode Should be the postcode you wish to find the information for
     * @return array|boolean Returns array if information exist else returns false
     */
    public function postcodeDetails($postcode){
        if($this->programActive()){
            $xml = $this->get_data(self::$AFD_HOST.':'.self::$AFD_PORT.'/addresslookup.pce?postcode='.$postcode);
            if($xml->Address->Postcode != 'Error: Postcode Not Found'){
                $details['lat'] = $xml->Address->Latitude;
                $details['lng'] = $xml->Address->Longitude;
                return $details;
            }
        }
        return false;
    }
    
    /**
     * Returns the address details for a chosen address with the given key
     * @param string $key This should be the key from the address info previously retrieved
     * @return array|boolean Returns and array if key and address info exist else returns false
     */
    public function setAddress($key){
        if($this->programActive()){
            $xml = $this->get_data(self::$AFD_HOST.':'.self::$AFD_PORT.'/afddata.pce?Data=Address&Task=Retrieve&Fields=Standard&Key='.$key);
            if($xml->Result == 1){
                $organisation = (string)$xml->Item->Organisation;
                $property = (string)$xml->Item->Property;
                $street = (string)$xml->Item->Street;
                $locality = (string)$xml->Item->Locality;
                $town = (string)$xml->Item->Town;
                $county = (string)$xml->Item->PostalCounty;
                $this->latitude = (string)$xml->Item->Latitude;
                $this->longitude = (string)$xml->Item->Longitude;
                
                if($organisation){
                    $this->address1 = $organisation.', '.$property;
                    $this->address2 = $street;
                    $this->address3 = $locality;
                }
                else{
                    if(strlen($property) >= 3){$this->address1 = $property.', '.$street;}
                    else{$this->address1 = $street;}
                    $this->address2 = $locality;
                    $this->address3 = '';
                }
                $this->town = $town;
                $this->county = $county;
                return true;
            }
        }
        return false;
    }
    
    /**
     * Returns the latitude of the last address location that was searched for
     * @return string|boolean 
     */
    public function getLatitude(){
        if(!empty($this->latitude)){
            return $this->latitude;
        }
        return false;
    }
    
    /**
     * Returns the longitude of the last address location that was searched for
     * @return string|boolean 
     */
    public function getLongitude(){
        if(!empty($this->longitude)){
            return $this->longitude;
        }
        return false;
    }
    
    /**
     * Checks to see if the program is active for the given location
     * @return boolean Returns true if program active else returns false
     */
    public function programActive(){
        $statusxml = $this->get_data(self::$AFD_HOST.':'.self::$AFD_PORT.'/status.pce');
        return $statusxml->PCEStatus == 'OK' ? true : false;
    }
    
    /**
     * Gets the information from the URL given in XML format and turns it to an array
     * @param string $url This should be the URL with the given information
     * @return array Returns the results from the URL given in an array format  
     */
    private function get_data($url){
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        $xmlobj = simplexml_load_string($data);
        curl_close($ch);
        return $xmlobj;
    }
}