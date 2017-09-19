<?php 
$filename = $_GET['filename'];
$excel_link = $_GET['excel_link'];

// echo $filename;

$command = 'python ' . $_SERVER["DOCUMENT_ROOT"] . '/cgi-bin/gemmer/write_excel_file.py ' . $filename . " 2>&1";
exec($command, $out, $status);

// print output
foreach($out as $result) {
    if (strlen($result) > 1) {
        // if traceback in $result add bootstrap alert around the coming text until end of loop
        echo $result . "<br/>";
    }
}

if($status == 0) {
    $javascript = <<<EOT
<script>
    window.location.href = "{$excel_link}";
</script>
EOT;
}
else {
    echo '<p>Something went wrong in the Excel file generation!</p>';
}

echo $javascript;
?>