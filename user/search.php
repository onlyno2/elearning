<?php
    require('login/db.php');
	
    /*$query = $_GET['query']; 
    // gets value sent over search form*/
    if ($_GET['query']){
		$min_length = 0;
    // you can set minimum length of the query if you want
     $query = $_GET['query'];
	 if(strlen($query) >= $min_length){ // if query length is more or equal minimum length then
         
        $query1 = htmlspecialchars($query); 
        // changes characters used in html to their equivalents, for example: < to &gt;
         
        $query1 = pg_escape_string($query1);
        // makes sure nobody uses SQL injection
		$table='"Course"';
		$que=pg_prepare($con,"Search",'SELECT * FROM "Course" WHERE "name" ILIKE $1 ');
		$raw_result = pg_execute($con,"Search",array("%".$query1."%")) or die(pg_errormessage($con));
         
        /*$raw_results = mysql_query("SELECT * FROM articles
            WHERE (`title` LIKE '%".$query."%') OR (`text` LIKE '%".$query."%')") or die(mysql_error());*/
	    
             
        // * means that it selects all fields, you can also write: `id`, `title`, `text`
        // articles is the name of our table
         
        // '%$query%' is what we're looking for, % means anything, for example if $query is Hello
        // it will match "hello", "Hello man", "gogohello", if you want exact match use `title`='$query'
        // or if you want to match just full word so "gogohello" is out use '% $query %' ...OR ... '$query %' ... OR ... '% $query'
         
        if(pg_num_rows($raw_result) > 0){ // if one or more rows are returned do following
             
            /*while($results = mysql_fetch_array($raw_results)){
            // $results = mysql_fetch_array($raw_results) puts data from database into array, while it's valid it does the loop
             
                echo "<p><h3>".$results['title']."</h3>".$results['text']."</p>";
                // posts results gotten from database(title and text) you can also show id ($results['id'])
            }*/
			header("Location: search_results.php?query=$query");
             
        }
        else{ // if there is no matching rows do following
            header("Location: search_results.php");
        }
         
    }
	}
	else {
		echo "No results";
	}
    /*else{ // if query length is less than minimum
        echo "Minimum length is ".$min_length;
    }*/

    
?>