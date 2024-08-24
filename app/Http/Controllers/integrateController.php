<?php

namespace App\Http\Controllers;

use App\Models\Datacrm;
use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class integrateController extends Controller
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
    

    public function __construct() {

        $this->crm = new Datacrm('admin','3MU6ed0NGYvk0Cuk','https://server8.datacrm.la/datacrm/sierrasyequipos/');
    }


    /**
     * Undocumented function
     *
     * @return void
     */
    public function test(){


        /*
        $list = Db::table('referencias_gru')->orderby('descripcion')->get();

        $params = array();
        foreach($list as $out){

            if($out->descripcion != ''){
                $clean = preg_replace("/[^A-Za-z0-9 ]/", '', $out->descripcion);
                array_push($params,['module' => 'Products',
                                'picklist' => 'cf_1246',
                                'value' => $clean.' - '.$out->grupo]);
            }

        }*/
           
       


        $params = array('id'=>'14x107440', 'iva_check_value' => 0);
        $this->crm->operation('checkIVA',['element'=>json_encode($params)],'POST');
       
       // dd($params);

       // $this->crm->checkIva ($params);

     

     $info = $this->crm->searchRegister('Products',array('id'=>'14x107440'));

    
    public function loadallProducts(){
        

        try{
        
         $general = DB::table('referencias')->where('maneja_inventario','0')
                       
                        ->leftJoin('referencias_gru','referencias_gru.grupo','referencias.grupo')
                        ->leftJoin('referencias_sub', function($join){
                            $join->on('referencias_sub.subgrupo', '=', 'referencias.subgrupo');
                            $join->on('referencias_sub.grupo', '=', 'referencias_gru.grupo');

                        })
                
                        ->leftJoin('referencias_cla','referencias_cla.clase','referencias.clase')
                        ->leftJoin('referencias_sto','referencias_sto.codigo','referencias.codigo')->where('mes','6')->where('ano','2024')
                        ->select('referencias.*','referencias_gru.group as group_id','referencias_gru.descripcion as gru_name','referencias_sub.descripcion as sub_name',
                                'referencias_cla.clase as clase_id','referencias_cla.descripcion as clase_name','referencias_sto.bodega'
                                ,'referencias_sto.can_ini','referencias_sto.can_ent','referencias_sto.can_sal');


        $totalref = $general->count();

        $pages = ($totalref / 100);


        for($i = 0 ; $i < $pages ; $i++ ){

            $skip = $i * 100;
            
            
            $reference = $general->skip($skip)->take(100)->get();

            
            foreach($reference as $out){

                $tipo = 'Servicio';
                if($out->maneja_inventario == 1){
                    $tipo = 'Inventario';
                }


                $code = str_replace("`", "", $out->codigo);

                //$month = date('n');
                $year  = date('Y');


               // $stock = DB::table('referencias_sto')->where('codigo',$code)->where('mes','6')->where('ano',$year)->get();


                 //   foreach($stock as $bod ){

                        $total = $out->can_ini + $out->can_ent + $out->can_sal;
                        $datainfo = array(

                            'productname'       => $code,
                            'cf_1266'           => $code,
                            'cf_1286'           => $out->descripcion,
                            'cf_1238'           => $tipo,
                            'unit_price'        => $out->valor_unitario,
                            'tax1'              => $out->porcentaje_iva,
                            'tax1_check'        => true,
                            'assigned_user_id'  => '19x24',
                            'qtyinstock'        => $total,
                            'cf_1246'           => $out->gru_name.'-'.$out->group_id,
                            'cf_1240'           => $out->sub_name,
                            'segmento_products' => $out->clase_name.'-'.$out->clase_id,
                            'discontinued'      => true,
                            'cf_1264'           => $out->bodega,
                            'usageunit'         => $out->und_1,
                            'cf_1270'           => ($out->maneja_otra_und== 'S') ?$out->otra_und :'',
                            'cf_1294'           =>  $tipo
                           
                        
                            
                        );


                        $exits = $this->crm->searchRegister('Products',array('cf_1266' => $code, 'cf_1264' => $out->bodega));

                        if($exits == null){
                            $result = $this->crm->createRegister('Products',$datainfo);
                        }
                        else{
                            $datainfo['id'] = $exits['id'];    
                            $this->crm->updateRegister('Products',$datainfo);
                        }
                  //  }
                //}
    
            }

            
           
        }    

       }catch(\Exception $e){

            Log::channel('daily')->error($e->getMessage());
            return true;

        }

      return true;
    }


    /**
     * Undocumented function
     *
     * @return void
     */
    public function companiestoCrmAll(){

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



    public function createPotential(Request $request){

        $id = $request->id;

        try{
       
            $qoute = $this->crm->searchRegister('Quotes',['id'=>$id]);
       
            //User 
            $user = $this->crm->searchRegister('Users',['id'=>$qoute['assigned_user_id']]);

            //validaciones
            if($user['nick_name'] == '')
                return true;

            $dmsuser = Db::table('usuarios')->where('DataCRM_Email',$user['email1'])->first();

            if($dmsuser == null)
                return true;
      
            // Company 
            $company_id  = $qoute['account_id'];
            $companyData = $this->crm->searchRegister('Accounts',array('id'=>$company_id));

            /*
            if($companyData == null){

                return true;

            }else{
                $company_nit = $companyData['siccode'];

                $now = Carbon::now('America/Bogota');

                $infoData = array(

                    'nit'           => $companyData['siccode'],
                    'digito'        => $companyData['cf_1200'],
                    'nombres'       => $companyData['accountname'],
                    'telefono_1'    => $companyData['phone'],
                    'mail'          => $companyData['email1'], 
                    'nit_real'      => $companyData['siccode'],
                    'usuario'       => $dmsuser->usuario,
                    'fecha_creacion'=> $now->format('Y-m-d\TH:i:s.v'),
                    'gran_contribuyente'    => $companyData['cf_1202'],
                    'autoretenedor'         => $companyData['cf_1204'],
                    'tipo_identificacion'   => $this->tipo_identificacion[$companyData['cf_1198']],
                    'razon_comercial'       => $companyData['accountname'],

                    'concepto_14'           => $companyData['cf_1208'],

                    //'id_definicion_tributaria_tipo' => 'cf_1212'

                );


                $total = Db::table('terceros')->where('nit_real',$companyData['siccode'])->count();

                if($total == 0){

                    $dbComapny = DB::table('terceros')->insert($infoData);
                }
            }


            if($dbComapny == false){
            
                return true;

            }else{

                $contactdata = $this->crm->searchRegister('Contacts',array('id'=>$qoute['contact_id']));

                if($contactdata != null){

                        if($contactdata['cf_1268'] == ''){
                            $dirs = DB::table('CRM_contactos')->where('nit',$company_nit)->orderby('contacto','desc')->select('contacto')->first();

                            if( $dirs == null){
                                $contacto  = 1;
                            }else{
                                $contacto = $dirs->contacto + 1;
                            }
                        }else{
                            $contacto  = $contactdata['cf_1268'];
                        }

                    $infoData = array(

                        'nit'       => $company_nit,
                        'nombre'    => $contactdata['lastname'],
                        'contacto'  => $contacto

                    );

                    if($contactdata['cf_1268'] == ''){

                        DB::table('CRM_contactos')->insert($infoData);
                        $this->crm->updateRegister('Contacts',array(
                            'id'        => $contactdata['id'],
                            'cf_1268'   => $contacto,
                        ));

                    }else{


                        DB::table('CRM_contactos')->where('nit',$company_nit)->where('contacto',$contacto)->update($infoData);

                    }



                }


            }*/

            //Potential

            $company_nit = $companyData['siccode'];
            $now = Carbon::now('America/Bogota');


            if($qoute['cf_1288'] != ''){ 

                $bodega = $qoute['cf_1288'];
                $split = explode('-',$bodega);
                $numbod = trim($split[0]);

            }else{
                $numbod = 1;
            }

            $complete = str_pad($numbod, 6, "0", STR_PAD_LEFT);

            $docped = Db::table('consecutivos')->where('tipo','ZPE2'.$complete)->orderby('siguiente','desc')->select('siguiente')->first();

            $numero = $docped->siguiente + 1;

            $infoPotential = array(
                'usuario'           => $dmsuser->usuario,
                'sw'                => '2',
                'nit'               => $company_nit,
                'fecha'             => $now->format('Y-m-d\TH:i:s.v'),
                'vendedor'          => $dmsuser->nit,
                'valor_total'       => $qoute['hdnGrandTotal'],
                'usuario_inicial'   => $dmsuser->usuario,
                'bodega'            => $numbod,
                'numero'            => $numero,
                'anulado'           => 0,
                'CONTROL_MOD'       => 'N',
                'iva_fletes'        => 0,
                'fletes'            => 0,
                'descuento_des_iva' => 0,
                'condicion'         => 0,
                'dias_validez'      => 365,
                'condicion'         => 30,
                'descuento_pie'     => 0,
                'fecha_hora'        => $now->format('Y-m-d\TH:i:s.v'),
                'Lista_Precios'     => 1,
                'codigo_direccion'  => 0,
                'pc'                => 'CRM',
                'concepto'          => 1,

            );

            $insert = Db::table('documentos_ped')->insert($infoPotential);

            if($insert == true){
                Db::table('consecutivos')->where('tipo','ZPE2'.$complete)->update(['siguiente'=>$numero]);

            }

            $updq['cf_1252'] = $numero.'-'.$numbod;
            $updq['id']      = $id;
            $this->crm->updateRegister('Quotes',$updq);

        $i=1;
        foreach($qoute['LineItems'] as $items){

            $product = $this->crm->searchRegister('Products',['id' => $items['productid']]);

            $value_inside_brackets = null;
            $otra_cantidad = null;


            $unidad = $product['usageunit'];

            if(isset($items['comment']) != ''){
                preg_match('/\[(.*?)\]/', $items['comment'], $matches);
                $value_inside_brackets = $matches[1];

                if($value_inside_brackets == '' || $value_inside_brackets < 1){
                    $value_inside_brackets = 1;
                }

                $otra_cantidad = round($items['quantity'] / $value_inside_brackets,3);

                $unidad = $product['cf_1270'];

            }


            if($product['cf_1264'] != '')
                $key = array_search($product['cf_1264'], $this->bodega);
            else
                $key = 1;


            $item = array(

                'sw'       => '2',
                'seq'      => $i,
                'numero'   => $numero,
                'codigo'   => $product['cf_1266'],
                'valor_unitario'    => $items['listprice'],
                'porcentaje_iva'    => $items['tax1'],
                'bodega'            => (!empty($key))?$key:$numbod,
                'porcentaje_descuento'  => $items['discount_percent'],
                'cantidad_despachada'   => 0,
                'cantidad'              => $items['quantity'],
                'cantidad_otra_und'     => $otra_cantidad,
                'cantidad_dos'          => $value_inside_brackets,
                'und'                   => $unidad,
                'despacho_virtual'      => 0,
                'porc_dcto_2'           => 0,
                'porc_dcto_3'           => 0,

            );

            $i++;
            
            Db::table('documentos_lin_ped')->insert($item);
    
        }

        }catch(\Exception $e){

            Log::channel('daily')->error($e->getMessage());
            return true;

        }


        return true;

   }

    /**
     * Undocumented function
     *
     * @return void
     */
   



    public function updateProdsCrm(){

        $now = Carbon::now('America/Bogota');

        $newvalue = $now->subMinutes(3)->toDateTimeString();

        $totalref = DB::table('referencias')->whereDate('fecha_actualizacion','>=', $newvalue )->count();

        $pages = ($totalref / 100);

        for($i = 0 ; $i < $pages ; $i++ ){
            
            $skip = $i * 100;
            
            $reference = DB::table('referencias')
                        ->whereDate('referencias.fecha_actualizacion','>=', $newvalue )
                        ->leftJoin('referencias_gru','referencias_gru.grupo','referencias.grupo')
                        ->leftJoin('referencias_sub', function($join){
                            $join->on('referencias_sub.subgrupo', '=', 'referencias.subgrupo');
                            $join->on('referencias_sub.grupo', '=', 'referencias_gru.grupo');

                        })
                      /*  ->leftJoin('referencias_sub2', function($join2){
                            $join->on('referencias_sub2.subgrupo', '=', 'referencias.subgrupo');
                            $join->on('referencias_sub2.grupo', '=', 'referencias_gru.grupo');
                            $join->on('referencias_sub2.grupo', '=', 'referencias_gru.grupo');

                        })*/
                        ->leftJoin('referencias_cla','referencias_cla.clase','referencias.clase')
                        ->leftJoin('referencias_sto','referencias_sto.codigo','referencias.codigo')->where('mes','6')->where('ano','2024')
                        ->select('referencias.*','referencias_gru.descripcion as gru_name','referencias_sub.descripcion as sub_name',
                                'referencias_cla.descripcion as clase_name','referencias_sto.bodega'
                                ,'referencias_sto.can_ini','referencias_sto.can_ent','referencias_sto.can_sal')
                        ->skip($skip)->take(100)->get();


                        dd($reference);

            foreach($reference as $out){

                $tipo = '';
                if($out->maneja_inventario == 1)
                    $tipo = 'Inventario';


                $code = str_replace("`", "", $out->codigo);

                $month = date('n');
                $year  = date('Y');

        
                $total = $out->can_ini + $out->can_ent + $out->can_sal;
                $datainfo = array(

                    'productname'       => $code,
                    'cf_1266'           => $code,
                    'cf_1248'           => $out->descripcion,
                    'cf_1238'           => $tipo,
                    'unit_price'        => $out->valor_unitario,
                    'tax1'              => $out->porcentaje_iva,
                    'assigned_user_id'  => '19x24',
                    'qtyinstock'        => $total,
                    'cf_1246'           => $out->gru_name,
                    'cf_1240'           => $out->sub_name,
                    'segmento_products' => $out->clase_name,
                    'discontinued'      => true,
                    'cf_1264'           => $this->bodega[$out->bodega]
                    
                    
                );

                $search = $this->crm->searchRegister('Products',array('cf_1266' => $code,'cf_1264'=> $this->bodega[$out->bodega]));
                if($search != null){

                    $datainfo['id'] = $search['id'];
                    $this->crm->updateRegister('Products',$datainfo);

                }else{

                    $result = $this->crm->createRegister('Products',$datainfo);
                }
                   
            }       
                
        }
    }


    /**
     * Undocumented function
     *
     * @param Request $request
     * @return void
     */
    public function updateCompany(Request $request){


        $id = $request->id;

        $companyData = $this->crm->searchRegister('Accounts',array('id'=>$id));

        $user = $this->crm->searchRegister('Users',['id'=>$companyData['assigned_user_id']]);

        //validaciones
        $dmsuser = Db::table('usuarios')->where('nit',$user['nick_name'])->first();

        if($companyData == null || $dmsuser == null){

            return true;

        }else{
            $company_nit = $companyData['siccode'];

            $now = Carbon::now('America/Bogota');

            $infoData = array(

                'nit'           => $companyData['siccode'],
                'digito'        => $companyData['cf_1200'],
                'nombres'       => $companyData['accountname'],
                'telefono_1'    => $companyData['phone'],
                'mail'          => $companyData['email1'], 
                'nit_real'      => $companyData['siccode'],
                'usuario'       => $dmsuser->usuario,

                'gran_contribuyente'    => $companyData['cf_1202'],
                'autoretenedor'         => $companyData['cf_1204'],
                'tipo_identificacion'   => $this->tipo_identificacion[$companyData['cf_1198']],
                'razon_comercial'       => $companyData['accountname'],
                'concepto_14'           => $companyData['cf_1208'],
                'direccion'             => $companyData['bill_street'],
                'mail'                  => $companyData['email1'],

            );


            $total = Db::table('terceros')->where('nit',$companyData['siccode'])->count();
            if($total == 1){
            
                $dbComapny = DB::table('terceros')->where('nit',$companyData['siccode'])->update($infoData);
                

            }elseif($total == 0){

                $dbComapny = DB::table('terceros')->insert($infoData);

            }
        }

        return true;
    }


        /**
     * Undocumented function
     *
     * @param Request $request
     * @return void
     */
    public function updateContact(Request $request){

    
        $id = $request->id;
        $contactdata = $this->crm->searchRegister('Contacts',array('id' => $id));

        Log::channel('daily')->error($contactdata['account_id']);

        if($contactdata['account_id'] == '')
            return true;


        $company     = $this->crm->searchRegister('Accounts',array('id' => $contactdata['account_id'] ));

        if($contactdata != null && $company != null){

            if($contactdata['cf_1268'] == ''){
                    $dirs = DB::table('CRM_contactos')->where('nit',$company['siccode'])->orderby('contacto','desc')->select('contacto')->first();

                    if( $dirs == null){
                        $contacto  = 1;
                    }else{
                        $contacto = $dirs->contacto + 1;
                    }
            }else{
                    
                    $cont = explode('-',$contactdata['cf_1268']);
                    $contacto  = $cont[1];
            }

            $infoData = array(

                'nit'       => $company['siccode'],
                'nombre'    => $contactdata['lastname'],
                'tel_ofi1'  => $contactdata['cf_1276'],
                'tel_celular'  => $contactdata['mobile'],
                'ext1'         => $contactdata['cf_1278'],
                'tel_ofi2'     => $contactdata['cf_1280'],
                'ext2'         => $contactdata['cf_1282'],
                'cargo'        => $contactdata['cf_1274'],
                'es_electronico' => ($contactdata['cf_1284'] == true)? 'S':null,


            );

            if($contactdata['cf_1268'] == ''){

                DB::table('CRM_contactos')->insert($infoData);
                $this->crm->updateRegister('Contacts',array(
                    'id'        => $contactdata['id'],
                    'cf_1268'   => $company['siccode'].'-'.$contacto,
                ));

            }else{

                DB::table('CRM_contactos')->where('nit',$company['siccode'])->where('contacto',$contacto)->update($infoData);

            }

        }

        return true;


    }



    /**
     * Undocumented function
     *
     * @param Request $request
     * @return void
     */
    public function updateQuote(Request $request){

        $id     = $request->id;
        $dmsid  = $request->dmsid;
        $bodega = $request->bodega;

        try{

            $num    = explode('-',$dmsid);


            $split  = explode('-',$bodega);
            $numbod = trim($split[0]);

            $realbodega = $num[1];
            $realnumber = $num[0];


          
            $qoute = $this->crm->searchRegister('Quotes',['id'=>$id]);

            $user = $this->crm->searchRegister('Users',['id'=>$qoute['assigned_user_id']]);
            $dmsuser = Db::table('usuarios')->where('DataCRM_Email',$user['email1'])->first();

            if($dmsuser == null)
                return true;
        

            if($realbodega != $numbod){

                
      
                // Company 
                $company_id  = $qoute['account_id'];
                $companyData = $this->crm->searchRegister('Accounts',array('id'=>$company_id));


                $company_nit = $companyData['siccode'];
                $now = Carbon::now('America/Bogota');
    
    
                if($qoute['cf_1288'] == ''){ 
    
                    $numbod = 1;
    
                }
    
                $complete = str_pad($numbod, 6, "0", STR_PAD_LEFT);
    
                $docped = Db::table('consecutivos')->where('tipo','ZPE2'.$complete)->orderby('siguiente','desc')->select('siguiente')->first();
    
                $numero = $docped->siguiente + 1;
    
                $infoPotential = array(
                    'usuario'           => $dmsuser->usuario,
                    'sw'                => '2',
                    'nit'               => $company_nit,
                    'fecha'             => $now->format('Y-m-d\TH:i:s.v'),
                    'vendedor'          => $dmsuser->nit,
                    'valor_total'       => $qoute['hdnGrandTotal'],
                    'usuario_inicial'   => $dmsuser->usuario,
                    'bodega'            => $numbod,
                    'numero'            => $numero,
                    'anulado'           => 0,
                    'CONTROL_MOD'       => 'N',
                    'iva_fletes'        => 0,
                    'fletes'            => 0,
                    'descuento_des_iva' => 0,
                    'condicion'         => 0,
                    'dias_validez'      => 30,
                    'condicion'         => 30,
                    'descuento_pie'     => 0,
                    'fecha_hora'        => $now->format('Y-m-d\TH:i:s.v'),
                    'Lista_Precios'     => 1,
                    'codigo_direccion'  => 0,
                    'pc'                => 'CRM',
                    'concepto'          => 1,
    
                );


                $insert = Db::table('documentos_ped')->where('sw','2')->where('numero',$realnumber)->where('bodega',$realbodega)->delete();

                if($insert == true){
                    Db::table('consecutivos')->where('tipo','ZPE2'.$complete)->update(['siguiente'=>$numero]);
    
                }
    
                $updq['cf_1252'] = $numero.'-'.$numbod;
                $updq['id']      = $id;
                $this->crm->updateRegister('Quotes',$updq);


            }else{


                $infoPotential = array(
                    'vendedor'          => $dmsuser->nit,
                    'valor_total'       => $qoute['hdnGrandTotal'],
                );
    
                Db::table('documentos_ped')->where('sw','2')->where('bodega',$realbodega)->where('numero',$realnumber)->update($infoPotential);

                $numero = $realnumber;
                $numbod = $realbodega;

            }

        Db::table('documentos_lin_ped')->where('sw','2')->where('bodega',$realbodega)->where('numero',$realnumber)->first();

        $i=1;
        foreach($qoute['LineItems'] as $items){

            $product = $this->crm->searchRegister('Products',['id' => $items['productid']]);

            $value_inside_brackets = null;
            $otra_cantidad = null;


            $unidad = $product['usageunit'];

            if(isset($items['comment']) != ''){
                preg_match('/\[(.*?)\]/', $items['comment'], $matches);
                $value_inside_brackets = $matches[1];

                if($value_inside_brackets == '' || $value_inside_brackets < 1){
                    $value_inside_brackets = 1;
                }

                $otra_cantidad = round($items['quantity'] / $value_inside_brackets,3);

                $unidad = $product['cf_1270'];

            }


            if($product['cf_1264'] != '')
                $key = array_search($product['cf_1264'], $this->bodega);
            else
                $key = 1;


            $item = array(

                'sw'       => '2',
                'seq'      => $i,
                'numero'   => $numero,
                'codigo'   => $product['cf_1266'],
                'valor_unitario'    => $items['listprice'],
                'porcentaje_iva'    => $items['tax1'],
                'bodega'            => (!empty($key))?$key:$numbod,
                'porcentaje_descuento'  => $items['discount_percent'],
                'cantidad_despachada'   => 0,
                'cantidad'              => $items['quantity'],
                'cantidad_otra_und'     => $otra_cantidad,
                'cantidad_dos'          => $value_inside_brackets,
                'und'                   => $unidad,
                'despacho_virtual'      => 0,
                'porc_dcto_2'           => 0,
                'porc_dcto_3'           => 0,

            );

            $i++;
            
            Db::table('documentos_lin_ped')->insert($item);
    
        }

        }catch(\Exception $e){

            
        }

    }

}
