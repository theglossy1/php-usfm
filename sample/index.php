<?php require_once ('../usfmProcess.php');

$ref = "Genesis 1";
$sfm = file_get_contents("GEN1-webbe.usfm");
$tagDecode = new UsfmTagDecoder();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv='content-type' content='text/html; charset=UTF-8' />
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $ref;?></title>
<link rel="stylesheet" type="text/css" href="usfm.css"/>
</head>
<body>
<?php echo $tagDecode->decode($sfm); ?>
</body>
</html>
