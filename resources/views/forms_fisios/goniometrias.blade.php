@extends('layouts.app')
@section('content')
@push("adminScripts")
<script src="{{ dsAsset('js/lib/country-list.js') }}"></script>
<script src="{{dsAsset('js/custom/forms_fisios/goniometrias.js')}}"></script>
@endpush

@push("adminCss")
<link href="{{ dsAsset('js/lib/jquery-schedule-plus/css/style.css')}}" rel="stylesheet" />
<link href="{{ dsAsset('css/custom/forms_fisios/fis-goniometrias.css')}}" rel="stylesheet" />
@endpush
<!-- Autor: Daniel's -->
<!-- Descripción: Formulario para Goniometry In Physiotherapy -->
<div class="page-inner">

    <!--Modal add menu-->
    <div class="modal fade" id="frmModal1" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form class="form-horizontal" id="inputForm" novalidate="novalidate" enctype="multipart/form-data">

                    
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <span class="fw-mediumbold">
                                {{translate('Goniometry In Physiotherapy')}}
                            </span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <!-- Necesrio en todos los formularios -->
                    <div class="modal-body">
                        <input type="hidden" name="id" id="id" value="0"> <!-- Necesario para carga el ID del registro a actualiza, valida si es insert o update -->
                        <div class="row" id='patientDiv'>
                            <div class="col-md-12 mt-3">
                                <div class="form-group">
                                    <label for="patient_id" class="float-left">Paciente<b class="color-red"> *</b></label>
                                    <div class="input-group">
                                        <select required id="patient_id" name="patient_id" class="form-control" data-live-search="true"></select>
                                        <div class="input-group-append">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12" id='NompatientDiv'>
                                <label>Paciente</label>
                                <input type="text" id="customer_name" name="customer_name" class="form-control" readonly>
                            </div>
                        </div>
                    <!-- Necesrio en todos los formularios -->

                        <div class="container">
                            <div class="alert alert-info" role="alert" >
                                <h5 class="text-center mb-3">
                               <strong>Instrucciones:</strong> 
                                <!-- Div para agregar instrucciones -->
                                <div class="d-flex flex-column flex-md-row flex-wrap justify-content-center gap-2">

                                </div>
                                <!-- Div para agregar instrucciones -->
                            </div>
                        </div>   

                        <div class="table-responsive">
                            

                    <!-- ================== HOMBRO ================== -->
                    <div class="container mt-4">
                        <h4 class="text-center fw-bold">HOMBRO</h4>

                        <!-- ================== FLEXIÓN - EXTENSIÓN ================== -->
                        <div class="row align-items-center mt-4">

                            <!-- Imagen Izquierda -->
                            <div class="col-lg-3 col-md-4 text-center">
                                 <p class="mt-2 fw-bold">IZQUIERDO<br>Flexión - Extensión</p>
                                <div class="border p-2 bg-light">
                                    <img src="/img/hom_fle_iz.png" alt="Imagen IZQUIERDO" 
                                        class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>

                            <!-- Tabla Central -->
                            <div class="col-lg-6 col-md-4">
                                <table class="table table-bordered text-center align-middle">
                                    <thead class="table-warning">
                                        <tr>
                                            <th colspan="4">Flexión - Extensión</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="4">
                                                Flexión: 0° a 90° <br>
                                                Extensión: 0° a 45° (o hasta 60°)
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"><strong>IZQUIERDO</strong></td>
                                            <td colspan="2"><strong>DERECHO</strong></td>
                                        </tr>
                                        <tr>
                                            <td>FLEX</td>
                                            <td>EXT</td>
                                            <td>FLEX</td>
                                            <td>EXT</td>
                                        </tr>
                                        <tr>
                                        <td><input type="number" id="hombro_flex_izq" name="hombro_flex_izq" class="form-control form-control-sm input-grados  input-grados" placeholder="grados"></td>
                                        <td><input type="number" id="hombro_ext_izq"  name="hombro_ext_izq"  class="form-control form-control-sm input-grados  input-grados" placeholder="grados"></td>
                                        <td><input type="number" id="hombro_flex_der" name="hombro_flex_der" class="form-control form-control-sm input-grados  input-grados" placeholder="grados"></td>
                                        <td><input type="number" id="hombro_ext_der"  name="hombro_ext_der"  class="form-control form-control-sm input-grados  input-grados" placeholder="grados"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Imagen Derecha -->
                            <div class="col-lg-3 col-md-4 text-center">
                                <p class="mt-2 fw-bold">DERECHO<br>Extensión - Flexión</p>
                                <div class="border p-2 bg-light">
                                   <img src="/img/hom_fle_iz.png" alt="Imagen DERECHO" 
                                    class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                        </div>

                        <!-- ================== ADUCCIÓN - ABDUCCIÓN ================== -->
                        <div class="row align-items-center mt-5">
                            <!-- Imagen Izquierda -->
                            <div class="col-md-3 text-center">
                               <p class="mt-2 fw-bold">IZQUIERDO<br>Aducción-Abducción</p>
                                <div class="border p-2 bg-light">
                                    <img src="/img/hom_aduccion_iz.png" alt="Imagen IZQUIERDO" 
                                        class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>

                            <!-- Tabla Central -->
                            <div class="col-md-6">
                                <table class="table table-bordered text-center align-middle">
                                    <thead class="table-warning">
                                        <tr>
                                            <th colspan="4">Aducción - Abducción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="4">
                                                Abducción: 0° a 90° <br>
                                                (Rotación de omóplato) 120° - 180° <br>
                                                Aducción: 90° a 0°
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"><strong>IZQUIERDO</strong></td>
                                            <td colspan="2"><strong>DERECHO</strong></td>
                                        </tr>
                                        <tr>
                                            <td>AD</td>
                                            <td>ABD</td>
                                            <td>AD</td>
                                            <td>ABD</td>
                                        </tr>
                                        <tr>
                                            <td><input type="number" id="hombro_ad_izq"  name="hombro_ad_izq"  class="form-control form-control-sm input-grados "  placeholder="grados"></td>
                                            <td><input type="number" id="hombro_abd_izq" name="hombro_abd_izq" class="form-control form-control-sm input-grados "  placeholder="grados"></td>
                                            <td><input type="number" id="hombro_ad_der"  name="hombro_ad_der"  class="form-control form-control-sm input-grados "  placeholder="grados"></td>
                                            <td><input type="number" id="hombro_abd_der" name="hombro_abd_der" class="form-control form-control-sm input-grados "  placeholder="grados"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Imagen Derecha -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">DERECHO<br>Aducción-Abducción</p>
                                <div class="border p-2 bg-light">
                                   <img src="/img/derecho.png" alt="Imagen DERECHO" 
                                    class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                        </div>

                        <!-- ================== ROTACIÓN ================== -->
                        <div class="row align-items-center mt-5">
                            <!-- Imagen Izquierda -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">IZQUIERDO<br>Rotación Interna</p>
                                <div class="border p-2 bg-light">
                                    <img src="/img/hom_rotacion_iz.png" alt="Imagen IZQUIERDO" 
                                        class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>

                            <!-- Tabla Central -->
                            <div class="col-md-6">
                                <table class="table table-bordered text-center align-middle">
                                    <thead class="table-primary">
                                        <tr>
                                            <th colspan="4">Rotación</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="4">
                                                Codo Flexionado: 0° a 90° <br>
                                                Rotación Externa: 0° a 60° <br>
                                                Rotación Interna: 0° a 80°
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"><strong>IZQUIERDO</strong></td>
                                            <td colspan="2"><strong>DERECHO</strong></td>
                                        </tr>
                                        <tr>
                                            <td>Rot. Int.</td>
                                            <td>Rot. Ext.</td>
                                            <td>Rot. Int.</td>
                                            <td>Rot. Ext.</td>
                                        </tr>
                                        <tr>
                                            <td><input type="number" id="hombro_rot_int_izq" name="hombro_rot_int_izq" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="hombro_rot_ext_izq" name="hombro_rot_ext_izq" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="hombro_rot_int_der" name="hombro_rot_int_der" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="hombro_rot_ext_der" name="hombro_rot_ext_der" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Imagen Derecha -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">DERECHO<br>Rotación Interna</p>
                                <div class="border p-2 bg-light">
                                   <img src="/img/hom_rotacion_der.png" alt="Imagen DERECHO" 
                                    class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                        </div>
                    </div>

                                        <!-- ================== CODO ================== -->
                    <div class="container mt-5">
                        <h4 class="text-center fw-bold">CODO</h4>

                        <!-- Flexión - Extensión -->
                        <div class="row align-items-center mt-4">
                            <div class="col-md-3 text-center">
                               <p class="mt-2 fw-bold">IZQUIERDO<br>Flexión - Extensión</p>
                                <div class="border p-2 bg-light">
                                    <img src="/img/izquierdo.png" alt="Imagen IZQUIERDO" 
                                        class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered text-center align-middle">
                                    <thead class="table-warning">
                                        <tr><th colspan="4">Flexión - Extensión</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="4">
                                                Flexion: 0° a 150° <br>
                                                Extensión: 150° a 0° <br>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"><strong>IZQUIERDO</strong></td>
                                            <td colspan="2"><strong>DERECHO</strong></td>
                                        </tr>
                                        <tr>
                                            <td>FLEX</td><td>EXT</td><td>FLEX</td><td>EXT</td>
                                        </tr>
                                        <tr>
                                            <td><input type="number" id="codo_flex_izq" name="codo_flex_izq" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="codo_ext_izq" name="codo_ext_izq"   class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="codo_flex_der" name="codo_flex_der" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="codo_ext_der" name="codo_ext_der"   class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Imagen DERECHA -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">DERECHO<br>Extensión - Flexión</p>
                                <div class="border p-2 bg-light">
                                   <img src="/img/cod_flexion_der.png" alt="Imagen DERECHO" 
                                    class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                        </div>

                        <!-- Pronación - Supinación -->
                        <div class="row align-items-center mt-4">
                            <!-- Imagen IZQUIERDA -->
                            <div class="col-md-3 text-center">
                               <p class="mt-2 fw-bold">IZQUIERDO<br>Supinación-Pronación</p>
                                <div class="border p-2 bg-light">
                                    <img src="/img/cod_supinacion.png" alt="Imagen IZQUIERDO" 
                                        class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered text-center align-middle">
                                    <thead class="table-warning">
                                        <tr><th colspan="4">Pronación - Supinación</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="4">
                                                Pronación: 0° a 80° <br>
                                                Supinación: 0° a 80° <br>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"><strong>IZQUIERDO</strong></td>
                                            <td colspan="2"><strong>DERECHO</strong></td>
                                        </tr>
                                        <tr>
                                            <td>PRO</td><td>SUP</td><td>PRO</td><td>SUP</td>
                                        </tr>
                                        <tr>
                                            <td><input type="number" id="codo_pro_izq" name="codo_pro_izq" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="codo_sup_izq" name="codo_sup_izq" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="codo_pro_der" name="codo_pro_der" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="codo_sup_der" name="codo_sup_der" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Imagen DERECHA -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">DERECHO<br>Supinación-Pronación</p>
                                <div class="border p-2 bg-light">
                                   <img src="/img/cod_supinacion.png" alt="Imagen DERECHO" 
                                    class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================== MUÑECA ================== -->
                    <div class="container mt-5">
                        <h4 class="text-center fw-bold">MUÑECA</h4>

                        <!-- Flexión - Extensión -->
                        <div class="row mt-4 align-items-stretch">
                            <!-- Imagen IZQUIERDA -->
                            <div class="col-md-3 text-center">
                               <p class="mt-2 fw-bold">IZQUIERDO<br>Flexión Dorsal-Palmar</p>
                                <div class="border p-2 bg-light">
                                    <img src="/img/flexion_dor_iz.png" alt="Imagen IZQUIERDO" 
                                        class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>

                            <!-- Tabla -->
                            <div class="col-md-6 d-flex align-items-center">
                                <table class="table table-bordered text-center align-middle">
                                    <thead class="table-info">
                                        <tr><th colspan="4">Flexión Dorsal - Flexión Palmar</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="4">
                                                Flexión Dorsal (extensión): 0° a 70° <br>
                                                Flexión Palmar: 0° a 80°
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"><strong>IZQUIERDO</strong></td>
                                            <td colspan="2"><strong>DERECHO</strong></td>
                                        </tr>
                                        <tr>
                                            <td>FLEX-D</td>
                                            <td>FLEX-P</td>
                                            <td>FLEX-D</td>
                                            <td>FLEX-P</td>
                                        </tr>
                                        <tr>
                                            <td><input type="number" id="muneca_flex_dorsal_izq" name="muneca_flex_dorsal_izq" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="muneca_flex_palmar_izq" name="muneca_flex_palmar_izq" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="muneca_flex_dorsal_der" name="muneca_flex_dorsal_der" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="muneca_flex_palmar_der" name="muneca_flex_palmar_der" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Imagen DERECHA -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">DERECHO<br>Flexión Dorsal-Palmar</p>
                                <div class="border p-2 bg-light">
                                   <img src="/img/flexion_dor_iz.png" alt="Imagen DERECHO" 
                                    class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                        </div>

                        <!-- Desviación: Radial - Cubital -->
                        <div class="row mt-4 align-items-stretch">
                            <!-- Imagen IZQUIERDA -->
                            <div class="col-md-3 text-center">
                               <p class="mt-2 fw-bold">IZQUIERDO<br>Dsv Radial-Desv Cubital</p>
                                <div class="border p-2 bg-light">
                                    <img src="/img/dsv_rad_iz.png" alt="Imagen IZQUIERDO" 
                                        class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>

                            <!-- Tabla -->
                            <div class="col-md-6 d-flex align-items-center">
                                <table class="table table-bordered text-center align-middle">
                                    <thead class="table-warning">
                                        <tr><th colspan="4">Desviación: Radial - Cubital</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="4">
                                                Desviación radial: 0° a 20° <br>
                                                Desviación cubital: 0° a 30°
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"><strong>IZQUIERDO</strong></td>
                                            <td colspan="2"><strong>DERECHO</strong></td>
                                        </tr>
                                        <tr>
                                            <td>Desv. Radial</td>
                                            <td>Desv. Cubital</td>
                                            <td>Desv. Cubital</td>
                                            <td>Desv. Radial</td>
                                        </tr>
                                        <tr>
                                            <td><input type="number" id="muneca_desv_radial_izq"  name="muneca_desv_radial_izq"  class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="muneca_desv_cubital_izq" name="muneca_desv_cubital_izq" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="muneca_desv_radial_der"  name="muneca_desv_radial_der"  class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="muneca_desv_cubital_der" name="muneca_desv_cubital_der" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Imagen DERECHA -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">DERECHO<br>Dsv Radial-Desv Cubital</p>
                                <div class="border p-2 bg-light">
                                   <img src="/img/dsv_rad_iz.png" alt="Imagen DERECHO" 
                                    class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- ================== CADERA ================== -->
                    <div class="container mt-5">
                        <h4 class="text-center fw-bold">CADERA</h4>

                        <!-- ===== Flexión Rodilla Recta ===== -->
                        <div class="row align-items-center mt-4">
                            <!-- Imagen IZQUIERDA -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">IZQUIERDO<br> Rodilla Recta</p>
                                <div class="border p-2 bg-light">
                                    <img src="/img/cad_flex_iz.png" alt="Imagen IZQUIERDO" 
                                        class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered text-center align-middle">
                                    <thead class="table-warning">
                                        <tr><th colspan="4">Flexión Rodilla Recta</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="4">
                                                Flexión: 0° a 90° <br>
                                                Extensión: 0° a 45° (o hasta 60°)
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"><strong>IZQUIERDO</strong></td>
                                            <td colspan="2"><strong>DERECHO</strong></td>
                                        </tr>
                                        <tr>
                                            <td>FL </td>
                                            <td>EX </td>
                                            <td>FL </td>
                                            <td>EX</td>
                                        </tr>
                                        <tr>
                                            <td><input type="number" id="cadera_flex_recta_izq" name="cadera_flex_recta_izq" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="cadera_flex_recta_der" name="cadera_flex_recta_der" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="cadera_ex_recta_izq"   name="cadera_ex_recta_izq"   class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="cadera_ex_recta_der"   name="cadera_ex_recta_der"   class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Imagen DERECHA -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">DERECHO<br> Rodilla Recta</p>
                                <div class="border p-2 bg-light">
                                   <img src="/img/cad_flex_der.png" alt="Imagen DERECHO" 
                                    class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                        </div>

                        <!-- ===== Flexión Rodilla Flexionada ===== -->
                        <div class="row align-items-center mt-5">
                            <!-- Imagen IZQUIERDA -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">IZQUIERDO<br> Rodilla Flexionada</p>
                                <div class="border p-2 bg-light">
                                    <img src="/img/cad_flex_rod_iz.png" alt="Imagen IZQUIERDO" 
                                        class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered text-center align-middle">
                                    <thead class="table-danger">
                                        <tr><th colspan="4">Flexión Rodilla Flexionada</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="4">
                                                Flexión: 0° a 120° <br>
                                                Extensión: 0° a 45° (o hasta 60°)
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"><strong>IZQUIERDO</strong></td>
                                            <td colspan="2"><strong>DERECHO</strong></td>
                                        </tr>
                                        <tr>
                                            <td>FLEX</td>
                                            <td>EXT</td>
                                            <td>FLEX</td>
                                            <td>EXT</td>
                                        </tr>
                                        <tr>
                                            <td><input type="number" id="cadera_flex_flexionada_izq" name="cadera_flex_flexionada_izq" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="cadera_ext_flexionada_izq"  name="cadera_ext_flexionada_izq"  class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="cadera_flex_flexionada_der" name="cadera_flex_flexionada_der" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="cadera_ext_flexionada_der"  name="cadera_ext_flexionada_der"  class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Imagen DERECHA -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">DERECHO<br> Rodilla Flexionada</p>
                                <div class="border p-2 bg-light">
                                   <img src="/img/cad_flex_rod_der.png" alt="Imagen DERECHO" 
                                    class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                        </div>

                        <!-- ===== Extensión ===== -->
                        <div class="row align-items-center mt-5">
                            <!-- Imagen IZQUIERDA -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">IZQUIERDO<br>Extensión</p>
                                <div class="border p-2 bg-light">
                                    <img src="/img/cad_ext_iz.png" alt="Imagen IZQUIERDO" 
                                        class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered text-center align-middle">
                                    <thead class="table-warning">
                                        <tr><th colspan="2">Extensión</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="2">Extensión de Cadera: 0° a 20°</td>
                                        </tr>
                                        <tr>
                                            <td colspan="1"><strong>IZQUIERDO</strong></td>
                                            <td colspan="1"><strong>DERECHO</strong></td>
                                        </tr>
                                        <tr>
                                            <td>EXT</td>
                                            <td>EXT</td>
                                        </tr>
                                        <tr>
                                            <td><input type="number" id="cadera_ext_izq" name="cadera_ext_izq" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="cadera_ext_der" name="cadera_ext_der" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Imagen DERECHA -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">DERECHO<br>Extensión</p>
                                <div class="border p-2 bg-light">
                                   <img src="/img/cad_ext_der.png" alt="Imagen DERECHO" 
                                    class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                        </div>

                        <!-- ===== Abducción y Aducción ===== -->
                        <div class="row align-items-center mt-5">
                            <!-- Imagen IZQUIERDA -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">IZQUIERDO<br>Abducción - Aducción</p>
                                <div class="border p-2 bg-light">
                                    <img src="/img/cad_abd_iz.png" alt="Imagen IZQUIERDO" 
                                        class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered text-center align-middle">
                                    <thead class="table-info">
                                        <tr><th colspan="4">Abducción y Aducción</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="4">
                                                Abducción: 0° a 45° <br>
                                                Aducción: 0° a 20°
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"><strong>IZQUIERDO</strong></td>
                                            <td colspan="2"><strong>DERECHO</strong></td>
                                        </tr>
                                        <tr>
                                            <td>ABD</td>
                                            <td>AD</td>
                                            <td>ABD</td>
                                            <td>AD</td>
                                        </tr>
                                        <tr>
                                            <td><input type="number" id="cadera_ad_izq"  name="cadera_ad_izq"  class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="cadera_abd_izq" name="cadera_abd_izq" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="cadera_ad_der"  name="cadera_ad_der"  class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="cadera_abd_der" name="cadera_abd_der" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Imagen DERECHA -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">DERECHO<br>Abducción - Aducción</p>
                                <div class="border p-2 bg-light">
                                   <img src="/img/cad_abd_der.png" alt="Imagen DERECHO" 
                                    class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                        </div>

                        <!-- ===== Rotación Externa e Interna ===== -->
                        <div class="row align-items-center mt-5">
                            <!-- Imagen IZQUIERDA -->
                            <div class="col-md-3 text-center">
                               <p class="mt-2 fw-bold">IZQUIERDO<br> Externa e Interna</p>
                                <div class="border p-2 bg-light">
                                    <img src="/img/cad_rot.png" alt="Imagen IZQUIERDO" 
                                        class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered text-center align-middle">
                                    <thead class="table-primary">
                                        <tr><th colspan="4">Rotación Externa e Interna</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="4">
                                                Rotación Externa: 0° a 45° <br>
                                                Rotación Interna: 0° a 45°
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"><strong>IZQUIERDO</strong></td>
                                            <td colspan="2"><strong>DERECHO</strong></td>
                                        </tr>
                                        <tr>
                                            <td>ROT INT</td>
                                            <td>ROT EXT</td>
                                            <td>ROT INT</td>
                                            <td>ROT EXT</td>
                                        </tr>
                                        <tr>
                                            <td><input type="number" id="cadera_rot_int_izq"  name="cadera_rot_int_izq" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="cadera_rot_ext_izq"  name="cadera_rot_ext_izq" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="cadera_rot_int_der"  name="cadera_rot_int_der" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="cadera_rot_ext_der"  name="cadera_rot_ext_der" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Imagen DERECHA -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">DERECHO<br> Externa e Interna</p>
                                <div class="border p-2 bg-light">
                                   <img src="/img/cad_rot.png" alt="Imagen DERECHO" 
                                    class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- ================== RODILLA ================== -->
                    <div class="container mt-5">
                        <h4 class="text-center fw-bold">RODILLA</h4>

                        <!-- ===== Flexión ===== -->
                        <div class="row align-items-center mt-4">
                            <!-- Imagen Izquierda -->
                            <div class="col-md-3 text-center">
                               <p class="mt-2 fw-bold">IZQUIERDO<br>Flexión</p>
                                <div class="border p-2 bg-light">
                                    <img src="/img/rod_flex_iz.png" alt="Imagen IZQUIERDO" 
                                        class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>

                            <!-- Tabla Flexión -->
                            <div class="col-md-6">
                                <table class="table table-bordered text-center align-middle">
                                    <thead class="table-danger">
                                        <tr>
                                            <th colspan="2">Flexión</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="2">
                                                Flexión: 0° a 135° <br>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="1"><strong>IZQUIERDO</strong></td>
                                            <td colspan="1"><strong>DERECHO</strong></td>
                                        </tr>
                                        <tr>
                                            <td>FLEXIÓN</td>
                                            <td>FLEXIÓN</td>
                                        </tr>
                                        <tr>
                                            <td><input type="number" id="rodilla_flex_izq" name="rodilla_flex_izq" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="rodilla_flex_der" name="rodilla_flex_der" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Imagen Derecha -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">DERECHO<br>Extensión</p>
                                <div class="border p-2 bg-light">
                                   <img src="/img/rod_flex_der.png" alt="Imagen DERECHO" 
                                    class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                        </div>

                        <!-- ===== Extensión ===== -->
                        <div class="row align-items-center mt-4">
                            <!-- Imagen Izquierda -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">IZQUIERDO<br>Extensión</p>
                                <div class="border p-2 bg-light">
                                    <img src="/img/izquierdo.png" alt="Imagen IZQUIERDO" 
                                        class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>

                            <!-- Tabla Extensión -->
                            <div class="col-md-6">
                                <table class="table table-bordered text-center align-middle">
                                    <thead class="table-success">
                                        <tr>
                                            <th colspan="2">Extensión</th>
                                        </tr>
                                    </thead>
                                    <tr>
                                            <td colspan="2">
                                                Extensión: 0° a 135° <br>
                                            </td>
                                    <tbody>
                                        <tr>
                                            <td colspan="1"><strong>IZQUIERDO</strong></td>
                                            <td colspan="1"><strong>DERECHO</strong></td>
                                        </tr>
                                        <tr>
                                            <td>EXTENSIÓN</td>
                                            <td>EXTENSIÓN</td>
                                        </tr>
                                        <tr>
                                            <td><input type="number" id="rodilla_ext_izq" name="rodilla_ext_izq" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                            <td><input type="number" id="rodilla_ext_der" name="rodilla_ext_der" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Imagen Derecha -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">DERECHO<br>Extensión</p>
                                <div class="border p-2 bg-light">
                                   <img src="/img/rod_ext_der.png" alt="Imagen DERECHO" 
                                    class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- ================== TOBILLO ================== -->
                    <div class="container mt-5">
                        <h4 class="text-center fw-bold">TOBILLO</h4>

                        <!-- ===== Flexión Plantar-Dorsal ===== -->
                        <div class="row align-items-center mt-4">
                            <!-- Imagen Izquierda -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">IZQUIERDO<br> Plantar-Dorsal</p>
                                <div class="border p-2 bg-light">
                                    <img src="/img/tob_flex_iz.png" alt="Imagen IZQUIERDO" 
                                        class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>

                            <!-- Tabla Flexión Plantar-Dorsal -->
                            <div class="col-md-6">

                        <table class="table table-bordered text-center align-middle">
                                <thead class="table-success">
                                <tr><th colspan="4">Flexión Plantar-Dorsal</th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                            <td colspan="4">
                                                Flexión Plantar: 0° a 45° <br>
                                                Flexión Dorsal, Dorsiflexión o Extensión: 0° a 45°
                                            </td>
                                <tr>
                                    <td colspan="2"><strong>IZQUIERDO</strong></td>
                                    <td colspan="2"><strong>DERECHO</strong></td>
                                </tr>
                                <tr>
                                    <td>FL Plantar</td>
                                    <td>FL Dorsal</td>
                                    <td>FL Plantar</td>
                                    <td>FL Dorsal</td>
                                </tr>
                                <tr>
                                    <td><input type="number" id="tobillo_flex_plantar_izq" name="tobillo_flex_plantar_izq" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                    <td><input type="number" id="tobillo_flex_dorsal_izq"  name="tobillo_flex_dorsal_izq"  class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                    <td><input type="number" id="tobillo_flex_plantar_der" name="tobillo_flex_plantar_der" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                    <td><input type="number" id="tobillo_flex_dorsal_der"  name="tobillo_flex_dorsal_der"  class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                            <!-- Imagen Derecha -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">DERECHO<br> Plantar-Dorsal</p>
                                <div class="border p-2 bg-light">
                                   <img src="/img/tob_flex_der.png" alt="Imagen DERECHO" 
                                    class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                        </div>
                        
                        <!-- ===== Eversión-Inversión ===== -->
                        <div class="row align-items-center mt-4">
                            <!-- Imagen Izquierda -->
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">IZQUIERDO<br>Eversión-Inversión</p>
                                <div class="border p-2 bg-light">
                                    <img src="/img/tob_ever.png" alt="Imagen IZQUIERDO" 
                                        class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>

                            <!-- Tabla Eversión-Inversión -->
                            <div class="col-md-6">
                        <table class="table table-bordered text-center align-middle">
                                    <thead class="table-success">
                                <tr><th colspan="4">Eversión-Inversión</th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                            <td colspan="4">
                                                Eversión: 0° a 25°<br>
                                                Inversión: 0° a 35°
                                            </td>
                                <tr>
                                    <td colspan="2"><strong>IZQUIERDO</strong></td>
                                    <td colspan="2"><strong>DERECHO</strong></td>
                                </tr>
                                <tr>
                                    <td>INV</td>
                                    <td>EV</td>
                                    <td>INV</td>
                                    <td>EV</td>
                                </tr>
                                <tr>
                                    <td><input type="number" id="tobillo_inversion_izq" name="tobillo_inversion_izq" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                    <td><input type="number" id="tobillo_eversion_izq"  name="tobillo_eversion_izq"  class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                    <td><input type="number" id="tobillo_inversion_der" name="tobillo_inversion_der" class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                    <td><input type="number" id="tobillo_eversion_der"  name="tobillo_eversion_der"  class="form-control form-control-sm input-grados " placeholder="grados"></td>
                                </tr>
                                </tbody>
                        </table>
                            </div>
                            <!-- Imagen Derecha --> 
                            <div class="col-md-3 text-center">
                                <p class="mt-2 fw-bold">DERECHO<br>Eversión-Inversión</p>
                                <div class="border p-2 bg-light">
                                   <img src="/img/tob_ever.png" alt="Imagen DERECHO" 
                                    class="img-fluid" style="max-height:150px;">
                                </div>
                            </div>
                        </div>
                    </div>
        
                            
                      </div>
                        
                      <div class="form-group control-group form-inline ">
                                <label>Diagnostico</label>
                                <input type="text" id="diagnostico" name="diagnostico" class="form-control input-full" >
                        </div>

                        <div class="form-group control-group form-inline ">
                            <label>{{translate('Observaciones')}}</label>
                            <textarea type="text" id="observaciones" name="observaciones"  class="form-control input-full"></textarea>
                            <span class="help-block"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-sm input-grados " data-dismiss="modal">{{translate('Close')}}</button>
                        <button type="submit" class="btn btn-success btn-sm input-grados ">{{translate('Save Change')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- category datatable -->
    <div class="row">
        <div class="col-md-12">
            <div class="main-card card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h4 class="card-title">
                            {{translate('Goniometry In Physiotherapy')}}  
                        </h4>
                        <button id="btnAdd" class="btn btn-primary btn-sm input-grados  btn-round ml-auto">
                            <i class="fa fa-plus"></i> {{translate('Add New')}}
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tableElement" class="table table-bordered w100"></table>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection

<script>
document.addEventListener('DOMContentLoaded', function () {
   
});
</script>