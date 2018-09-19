<?php
require_once "assets/script/vendor/autoload.php";
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\BlobSharedAccessSignatureHelper;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;
use MicrosoftAzure\Storage\Blob\Models\DeleteBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\GetBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\ContainerACL;
use MicrosoftAzure\Storage\Blob\Models\SetBlobPropertiesOptions;
use MicrosoftAzure\Storage\Blob\Models\ListPageBlobRangesOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Common\Exceptions\InvalidArgumentTypeException;
use MicrosoftAzure\Storage\Common\Internal\Resources;
use MicrosoftAzure\Storage\Common\Internal\StorageServiceSettings;
use MicrosoftAzure\Storage\Common\Models\Range;
use MicrosoftAzure\Storage\Common\Models\Logging;
use MicrosoftAzure\Storage\Common\Models\Metrics;
use MicrosoftAzure\Storage\Common\Models\RetentionPolicy;
use MicrosoftAzure\Storage\Common\Models\ServiceProperties;


if(isset($_POST['submit'])){
$encodedData = str_replace(' ','+',$_POST['theimage']);
$snapBlob = base64_decode($encodedData);

$connectionString = 'DefaultEndpointsProtocol=https;AccountName=emotionstats;AccountKey=uYwVWFujFuZW1wZJVTARcniAex1k062EXkA7DA5m8AwjBrM32Biy+tD0Rb6FrGZsxWzsLmqG2ED2rob9WCElRA==';
$blobClient = BlobRestProxy::createBlobService($connectionString);

uploadBlobSample($blobClient,$snapBlob);
echo generateBlobDownloadLinkWithSAS();


}

function uploadBlobSample($blobClient,$snapBlob)
{
    //$content = fopen("myfile.txt", "r");
    $blob_name = "image";
    
    try {
        //Upload blob
        $blobClient->createBlockBlob("emotion-images", $blob_name, $snapBlob);
    } catch (ServiceException $e) {
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message.PHP_EOL;
    }
}

function generateBlobDownloadLinkWithSAS()
{
    global $connectionString;

    $settings = StorageServiceSettings::createFromConnectionString($connectionString);
    $accountName = $settings->getName();
    $accountKey = $settings->getKey();

    $helper = new BlobSharedAccessSignatureHelper(
        $accountName,
        $accountKey
    );

    // Refer to following link for full candidate values to construct a service level SAS
    // https://docs.microsoft.com/en-us/rest/api/storageservices/constructing-a-service-sas
    $sas = $helper->generateBlobServiceSharedAccessSignatureToken(
        Resources::RESOURCE_TYPE_BLOB,
        'emotion-images/image',
        'r',                            // Read
        '2019-01-01T08:30:00Z'//,       // A valid ISO 8601 format expiry time
        //'2019-01-01T13:15:30TZD'//,       // A valid ISO 8601 format expiry time
        //'0.0.0.0-255.255.255.255'
        //'https,http'
    );

    $connectionStringWithSAS = Resources::BLOB_ENDPOINT_NAME .
        '='.
        'https://' .
        $accountName .
        '.' .
        Resources::BLOB_BASE_DNS_NAME .
        ';' .
        Resources::SAS_TOKEN_NAME .
        '=' .
        $sas;

    $blobClientWithSAS = BlobRestProxy::createBlobService(
        $connectionStringWithSAS
    );

    // We can download the blob with PHP Client Library
    // downloadBlobSample($blobClientWithSAS);

    // Or generate a temporary readonly download URL link
    $blobUrlWithSAS = sprintf(
        '%s%s?%s',
        (string)$blobClientWithSAS->getPsrPrimaryUri(),
        'image/png',
        $sas
    );

    file_put_contents("outputBySAS.png", fopen($blobUrlWithSAS, 'r'));
    return $blobUrlWithSAS;
}

?>