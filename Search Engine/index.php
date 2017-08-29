<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('memory_limit', '3000M');
require_once('simple_html_dom.php');

$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$selected= isset($_REQUEST['pagerank'])? $_REQUEST['pagerank'] : false;
$results = false;
$hashmap=array();
$file= fopen("mapLATimesDataFile.csv","r");

while($data=fgetcsv($file)){
    if(count($data)==2){
        $hashmap[$data[0]]=$data[1];
    }
    else{
        $hashmap[$data[0]]=$data[1].",".$data[2];
    }
}

if ($query)
{ 
  require_once('solr-php-client/Apache/Solr/Service.php');
  require_once('SpellCorrector.php');
  $solr = new Apache_Solr_Service('192.168.136.146',8983, '/solr/hw4');
  $query_list=explode(" ",$query);
  foreach ($query_list as $q){
	$spellCheck = SpellCorrector::correct($q);
	if ($spellCheck == strtolower($q)) {
        $spellCheck = "";
	}
  }
  
  if (get_magic_quotes_gpc() == 1){
    $query = stripslashes($query);
  }
  $param = ['q.op' => 'AND',];
  if($selected=="page"){
	$param['sort'] ="pageRankFile desc";
  }
  try{
    $results = $solr->search($query, 0, $limit, $param);
  }
  catch (Exception $e){
    die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
  }
}

?>
<html>
   <head>
    	<title>PHP Solr Client Example</title>
		<link rel="stylesheet" href="http://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
		<script src="http://code.jquery.com/jquery-3.2.1.js"></script>
		<script src="http://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
		<script src="StopWord.js"></script>
   </head>
   <body>
		<form  accept-charset="utf-8" method="get">
			<label for="q">Search:</label>
			<input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
			<input type="submit" value="Submit Query"/>
			<br><br>
			<input type="radio" name="pagerank" value="page" <?php if($selected=='page') echo 'checked'; ?> />PageRank
			<input type="radio" name="pagerank" value="solr" <?php if($selected!='page') echo 'checked'; ?> />Solr<br>
			<p id="as"></p>
		</form>
		<?php if ($results): ?>
				<?php
					$total = (int) $results->response->numFound;
					$start = min(1, $total);
					$end = min($limit, $total);
				?>
			<?php if ($spellCheck): ?>
				<div style="font-size: 14px;">Do you mean: <a href="http://localhost/gua/index.php?q=<?php echo $spellCheck; ?>"><?php echo $spellCheck; ?></a></div>
			<?php endif; ?>
				<div style="font-size: 14px;">Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
				<ol>
			<?php foreach ($results->response->docs as $doc): ?>
				<?php 
					$id = $doc->id;
					$real_code=explode('/',$id)[5];      
					$url = $hashmap[$real_code];
					$des = $doc->description;
					$title = $doc->title; 
					$otitle= $doc->dc_title; 
					$fileContent = file_get_html("D:/USC/CSCI572/LATimesData/LATimesData/LATimesDownloadData/".$real_code);	
					if ($fileContent!=null){
						$fileContent = $fileContent->plaintext;
					}
					
					$snippet="";
					$length = count(explode(".", $fileContent));
					for ($i = 0; $i < $length; $i++) {		
						if(stripos(explode(".", $fileContent)[$i]," ".$query." ")!== false||stripos(explode(".", $fileContent)[$i]," ".$query.".")) {
							$snippet=explode(".", $fileContent)[$i] . "...";
							break;
						}	
					}
					if ($snippet=="")  {
						$snippet="No snippet";
					}
				?>			   
				<a style=" text-decoration: none;color:1a0dab;font-size: 18px;" href="<?php echo $url; ?>"><?php echo $title? $title : $otitle? $otitle:"No title"; ?></a>  
				<br/>    
				<a style=" text-decoration: none; color:006621;font-size: 14px;" href="<?php echo $url; ?>"><?php echo $url; ?></a>
				<br/>
				<!--<?php echo $id ?>
				<br/>
				<?php echo $des?$des:"No description";?> -->
				<div style="color=545454;font-size: 14px;"><?php echo $snippet ?></div>
				<br><br>
			<?php endforeach; ?>
		<?php endif; ?>
		<script>
        $(function() {
            $("#q").autocomplete({
                source : function(request, response) {
                    $.ajax({
                        url : "http://192.168.136.146:8983/solr/hw4/suggest?wt=json&q=" + $("#q").val().toLowerCase().split(" ").pop(-1),
                        success : function(data) {
                            var suggestions = data.suggest.suggest[$("#q").val().toLowerCase().split(" ").pop(-1)].suggestions;
                            suggestions = $.map(suggestions, function (value, index) {
                                
								var prefix = "";
                                if ( $("#q").val().split(" ").length > 1) {
                                    prefix = $("#q").val().substring(0, $("#q").val().lastIndexOf(" ") + 1).toLowerCase();
                                }
                                if (!/^[0-9a-zA-Z]+$/.test(value.term)||String.isStopWord(value.term)) {
                                    return null;
                                }
                                return prefix + value.term;
                            });
                            response(suggestions.slice(0, 5));
                        },
                        dataType : 'jsonp',
                        jsonp : 'json.wrf'
                    });
                },
                minLength : 1
            });
        });
    </script>
	</body>
</html>