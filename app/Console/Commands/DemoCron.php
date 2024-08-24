<?php

namespace App\Console\Commands;

use App\Models\Datacrm;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DemoCron extends Command
{

    private $crm;


    private $tipo_identificacion = array(
        'NIT'           => 'N',
        'Extranjeria'   => 'E',
        'Cedula'        => 'C',
        'Otro'          => 'O',
        'Tarjeta Identidad' => 'T',
        'Pasaporte'         => 'P',
        'Registro Civil de Nacimiento'  => 'R',
        'Tarjeta de Extranjeria'        => 'E1',
        'Cedula de Extranjeria'         => 'E2',
        'Tipo de documento extranjero'  => 'E3',
        'Sin identificacion del exterior'   => 'E4',
        'Permiso especial de permanencia'   => 'PE',
        'Permiso '                          => 'PPT'

    );


       private $bodega = array(
       '1' => 'BODEGA PRINCIPAL',
       '2' => 'PRODUCCIÓN',
       '3' => 'DESPERDICIO',
       '4' => 'EN CONSIGNACION',
       '5' => 'BODEGA BOGOTA',
       '6' => 'BODEGA PRODUCCIÓN',
       '80' => 'REMISIONES',
       '85' => 'BODEGA CLIENTES',
       '90' => 'ALMACEN',
       '91' => 'ALMACEN DE BOGOTA',
       '98' => 'BODEGA TRANSPORTES TCC',
       '99' => 'BODEGA DE TRANSITO',
       '100' => 'CONSIGNACION CLIENTES',

    );



    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $this->crm = new Datacrm('admin','3MU6ed0NGYvk0Cuk','https://server8.datacrm.la/datacrm/sierrasyequipos/'); 

        try{

            $general = DB::table('terceros')
            ->leftJoin('Usuarios','Usuarios.nit','terceros.vendedor')
            ->leftJoin('terceros_6','terceros_6.concepto_6','terceros.concepto_6')
            ->leftJoin('terceros_7','terceros_7.concepto_7','terceros.concepto_7')
            ->leftJoin('terceros_8','terceros_8.concepto_8','terceros.concepto_8')
            ->leftJoin('y_paises','y_paises.pais','terceros.y_pais')
            ->select('terceros.*','terceros_6.descripcion as cf_1172'
            ,'terceros_7.descripcion as cf_1174','terceros_8.descripcion as cf_1029',
            'Usuarios.nit as usr','Usuarios.DataCRM_Email as demail',
            'y_paises.descripcion as country')
            ->orderBy('nit','desc');

        $totalref = $general->count();

        $pages = ceil($totalref / 100);


        for($i = 0 ; $i < $pages ; $i++ ){

            $skip = $i * 100;

            $thrids = $general->skip($skip)->take(100)->get();


            foreach($thrids as $out){


                $key = array_search($out->tipo_identificacion, $this->tipo_identificacion);

                $user = null;
                if($out->demail != '' && $out->demail != null)
                    $user = $this->crm->searchRegister('Users',array('email1'=>$out->demail) );


                $userid = '19x24';
                if($user != null){
                    $userid = $user['id'];
                }



                $dept = DB::table('y_departamentos')->where('departamento',$out->y_dpto)->where('pais',$out->y_pais)->first();
                $city = DB::table('y_ciudades')
                        ->where('ciudad',$out->y_ciudad)
                        ->where('departamento',$out->y_dpto)
                        ->where('pais',$out->y_pais)->first();

                $datainfo = array(
                    'siccode'       => $out->nit,
                    'cf_1200'       => $out->digito,
                    'accountname'   => $out->nombres,
                    'phone'         => $out->telefono_1,
                    'cf_1198'       => $key,
                    'cf_1202'       => $out->gran_contribuyente,
                    'cf_1204'       => $out->autoretenedor,
                    'cf_1206'       => $out->nit_real,
                    'bill_country'  => $out->country,
                    'cf_1033'       => (isset($dept->descripcion))? $dept->descripcion:'',
                    'cf_1172'       => $out->cf_1172,
                    'cf_1174'       => $out->concepto_7.' '.$out->cf_1174,
                    'cf_1029'       => $out->concepto_8.' '.$out->cf_1029,
                    'cf_1208'       => $out->concepto_14,
                    'cf_1035'       => (isset($city->descripcion))? $city->descripcion:'',
                    'assigned_user_id' => $userid,
                    'bill_street'   => $out->direccion,
                    'email1'        => $out->mail,
                    
                );


                    $exists = $this->crm->searchRegister('Accounts',array('cf_1206'=>$out->nit_real));

                    if($exists == null){

                        $company = $this->crm->createRegister('Accounts',$datainfo);

                    }else{

                        $datainfo['id'] = $exists['id'];
                        $company = $this->crm->updateRegister('Accounts',$datainfo);
                    }

                    if($company != null){

                        $result = DB::table('CRM_contactos')->where('nit',$out->nit);

                        $totalcontacts = $result->count();

                        if($totalcontacts > 0){
                            $contact = $result->get();

                            foreach($contact as $con){

                                $infoContact = array(

                                    'lastname'      => ($con->nombre != '')? $con->nombre:$con->apellidos,
                                    'mobile'        => $con->tel_celular,
                                    'email'         => $con->e_mail,
                                    'account_id'    => $company['id'],
                                    'cf_1268'        => $con->nit.'-'.$con->contacto,
                                    'mailingcountry' => 'COLOMBIA',
                                    'cf_1274'        => $con->cargo,
                                    'cf_1276'        => $con->tel_ofi1,
                                    'cf_1278'        => $con->ext1,
                                    'cf_1280'        => $con->tel_ofi2,
                                    'cf_1282'        => $con->ext2,
                                    'cf_1284'        => ($con->es_electronico == 'S')? TRUE:FALSE,

                                );


                                
                                $infoC  = $this->crm->searchRegister('Contacts',array('account_id'=>$company['id'],'cf_1268'=>$con->nit.'-'.$con->contacto));


                                if($infoC == null){

                                    $this->crm->createRegister('Contacts',$infoContact);

                                }else{
                                    $infoContact['id'] = $infoC['id'];
                                    $this->crm->updateRegister('Contacts',$infoContact);

                                }

                            }
                        }

                        

                    }

            }

           
        }
        }catch(\Exception $e){

            Log::channel('daily')->error($e->getMessage());
            return true;

        }
    }
}
