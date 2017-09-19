<?php
$dir = $_SERVER["DOCUMENT_ROOT"] . '/cgi-bin/gemmer/DB_genes_and_interactions.db';
$db = new SQLite3($dir);

$result = $db->query('SELECT standard_name, systematic_name, name_desc from genes ORDER BY standard_name ASC');
while($row = $result->fetchArray()){
    $data[] = Array( 
      '<a href="index.php?id=database&gene=' . $row["standard_name"] . '">' . $row["standard_name"] . '</a>',
      $row['systematic_name'],
      $row['name_desc']
    );
  }

$results = array('data'=>$data);
echo json_encode($results);

?>