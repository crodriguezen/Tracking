<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'TrackingEstafeta.class.php';

$arrCompanies = array("ESTAFETA");

$wayBill = "";
if (isset($_REQUEST['wayBill']) && trim($_REQUEST['wayBill']) != "") {
    $wayBill = trim($_REQUEST['wayBill']);
}

$parcel = "";
if (isset($_REQUEST['parcel']) && trim($_REQUEST['parcel']) != "" && in_array(strtoupper(trim($_REQUEST['parcel'])), $arrCompanies)) {
    $parcel = strtoupper(trim($_REQUEST['parcel']));
}

$status = "N/D";
$scheduledDeliveryDate = "N/D";
$serviceType = "N/D";
$trackingNumber = "N/D";
$addressee = "N/D";

$jsonTrackingDetails = null;
$jsonSignature = null;
$jsonHistory = null;

$addresseeSearch = "";
$getSignature = false;

$oTracking = null;
switch ($parcel) {
    case "ESTAFETA": 
        $oTracking = new TrackingEstafeta($wayBill);
        $addresseeSearch = "PDV3:";
        $getSignature = true;
        break;
}

$jsonTrackingDetails = $oTracking->getTrackingDetails();
$jsonHistory = $oTracking->getHistory();
if ($getSignature) {
    $jsonSignature = $oTracking->getSignature();   
}

if ( $jsonTrackingDetails->addressee !== "") {
    $status = "Entregado";
}
$scheduledDeliveryDate = $jsonTrackingDetails->scheduledDeliveryDate;
$company = $parcel;
$serviceType = $jsonTrackingDetails->serviceType;
$trackingNumber = $jsonTrackingDetails->trackingNumber;
$addressee = str_replace($addresseeSearch,"", $jsonTrackingDetails->addressee);
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <link href="./redtracking.css?v=1.0.0.0.0.0.0.0" rel="stylesheet">

        <title>RedShipp Tracking</title>
    </head>
    <body class="bg-light">
        <div class="container border mt-3">
            <div class="row bg-danger bg-gradient text-white ">
                <div class="col text-center">
                    <h1 class="p-2 fs-5">Página de rastreo</h1>
                </div>
            </div>
            <div class="row">
                <div class="col pt-2 text-center">
                    <img src="https://redecommerce.s3.us-east-2.amazonaws.com/public/images/red_shipp_logo_h_1x.png" alt="Red Shipp" />
                    <p>
                        Sigue el estatus de tu envío
                    </p>
                </div>
            </div>
            <div class="row bg-body-secondary">
                <div class="row">
                    <div class="col text-center">
                        <div class="row">
                            <div class="col">
                                <small class="text-secondary">Estatus</small> <br />
                                <span class="fw-semibold"><?php echo $status;?></span>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col">
                                <small class="text-secondary">Paquetería</small> <br />
                                <span class="fw-semibold"><?php echo $company;?></span>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col">
                                <small class="text-secondary">Tipo Servicio</small> <br />
                                <span class="fw-semibold"><?php echo $serviceType;?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col text-center">
                        <div class="row">
                            <div class="col">
                                <small class="text-secondary">Entrega estimada</small> <br />
                                <span class="fw-semibold"><?php echo $scheduledDeliveryDate;?></span>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col">
                                <small class="text-secondary">Número de guía</small> <br />
                                <span class="fw-semibold"><?php echo $wayBill;?></span>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col">
                                <small class="text-secondary">Código de rastreo</small> <br />
                                <span class="fw-semibold"><?php echo $trackingNumber;?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <hr/>
                <div class="row pb-3">
                    <div class="col text-center">
                        <small class="text-secondary">Recibió</small> <br />
                        <span class="fw-semibold"><?php echo str_replace("PDV3:","", $addressee);?></span>
                    </div>
                </div>
            </div>
            <div class="row bg-secondary bg-gradient p-1">
                <div class="col text-center">
                    <small class="text-white">Firma</small>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col text-center">
                    <?php
                    if ($jsonSignature != null && $jsonSignature->success) {
                    ?>
                    <img src="<?php echo $jsonSignature->base64;?>" />
                    <?php
                    }
                    ?>
                </div>
            </div>
            <div class="row bg-secondary bg-gradient p-1">
                <div class="col text-center">
                    <small class="text-white">Historial</small>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <ul class="history-list">
                    <?php
                    if ($jsonHistory->success) {
                        foreach($jsonHistory->history as $h) {
                            $date = $h->date;
                            $time = $h->time;
                            $place = $h->place;
                            $status = $h->status;
                            $main = (bool) $h->main;

                            $divMain = "";
                            $liMain = "";
                            if ($main) {
                                $divMain = "fw-bold text-danger";
                                $liMain = "main";
                            }
                    ?>
                        <li class="<?php echo $liMain;?>">
                            <div class="history-time <?php echo $divMain;?>"><?php echo $time . " - " . $date;?></div>
                            <p class="mb-0 pb-0">
                                <?php echo $place;?>
                            </p>
                            <span class="text-primary-emphasis">
                                <?php echo $status;?>
                            </span>
                        </li>
                    <?php
                        }
                    ?>
                    </ul>
                    <?php
                    } else {
                    ?>
                    <p class="text-center pt-2">
                        Aún no hay eventos registrados o no se ha podido recuperar el historial en este momento. Inténtalo más tarde.
                    </p>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</html>



