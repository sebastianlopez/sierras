<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Salaros\Vtiger\VTWSCLib\Session;
use Salaros\Vtiger\VTWSCLib\WSClient;

class Datacrm extends Model
{
    public $username;
    public $token;


    public function __construct($usern, $tkn, $link)
    {
       
        $this->username = $usern;
        $this->token    = $tkn;
        $this->url      = $link;

        $this->vt = new WSClient( $this->url, $this->username, $this->token);
  
    }


    /**
     * Undocumented function
     *
     * @param [type] $module
     * @return void
     */
    public function getFields($module){

        return $this->vt->modules->getOne($module);
    }


    public function getUserInfo(){

        return $this->vt->session->getUserInfo();

    }


    /**
     * Undocumented function
     *
     * @param [type] $module
     * @param [type] $info
     * @return void
     */
    public function createRegister($module, $info){

        try {

            $register = $this->vt->entities->createOne($module, $info);
            Log::channel('daily')->error('Saved '.$module.' '.$register['id']);
            return $register;

        } catch (\Exception $e) {

            $needle = 'There is already a record in the Contacts';
            if($module == 'Contacts' && strpos($e->getMessage(), $needle)){
                $move = $info['mobile'];
                unset($info['mobile']);
                $info['cf_1280'] = $move;

                $this->createRegister($module,$info);
            }

            $msg = ['method' => 'createRegister '.$module, 'data' => [$info], 'error' => $e->getMessage()];
            Log::channel('daily')->error(json_encode($msg));

            return null;

        }


    }


    /**
     * Undocumented function
     *
     * @param [type] $module
     * @param array $search
     * @return void
     */
    public function searchRegister($module,$search=array() ){

        try{

            $register = $this->vt->entities->findOne($module,$search);
            return $register;

        }catch(\Exception $e){

           
        }

    }


    /**
     * Undocumented function
     *
     * @param [type] $module
     * @param [type] $data
     * @return void
     */
    public function updateRegister($module,$data){


            $resgister = null;
            
            try {
                
                $resgister = $this->vt->entities->updateOne($module, $data['id'], $data);
    
            } catch (\Exception $e) {
    
                $msg = ['method' => 'updateRegister', 'data' => ['id' => $data['id']], 'error' => $e->getMessage()];
                Log::channel('datacrm')->error(json_encode($msg));
    
            }
    
            return $resgister;
    
    }


        /**
     * Undocumented function
     *
     * @return void
     */
    public function makeQuery($query ){

        return $this->vt->runQuery( $query );
    
    }




    //$operation, array $params = null, $method = 'POST'

    public function operation($operation,$params,$method = 'POST'){

        return $this->vt->invokeOperation($operation,$params,$method);
    }



  /**
   * Undocumented function
   *
   * @param [type] $params
   * @return void
   */
    public function pickbox($params){

        $info = $this->getUserInfo();
        
        $curl = curl_init();


        

        try{
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://server8.datacrm.la/datacrm/sierrasyequipos/webservice.php?operation=addPickList&sessionName='.$info['sessionName'].'',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>json_encode($params)));

            $response = curl_exec($curl);
            curl_close($curl);
        }catch(\Exception $e){

            $msg = ['method' => 'addPickList', 'data' => $params , 'error' => $e->getMessage()];
                Log::channel('datacrm')->error(json_encode($msg));
        }
            
    }

    /**
     * 
     */
    public function checkIva($params){


        $info = $this->getUserInfo();
        
        $curl = curl_init();


        try{
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://server8.datacrm.la/datacrm/sierrasyequipos/webservice.php?operation=checkIVA&sessionName='.$info['sessionName'].'',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>json_encode($params)));

            $response = curl_exec($curl);
            curl_close($curl);
        }catch(\Exception $e){

            dd($e);

            $msg = ['method' => 'checkIVA', 'data' => $params , 'error' => $e->getMessage()];
                Log::channel('datacrm')->error(json_encode($msg));
        }

        return true;
    }

}

/*

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://develop3.datacrm.la/jmoreno/cpjmsierrasyequipos/webservice.php?operation=checkIVA',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => 'sessionName=64aed1e66c4c0cc53133&element=%7B%22id%22%3A%20%2214x76148%22%2C%20%22iva_check_value%22%3A%201%7D',
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
*/