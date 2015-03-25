<?php
	set_time_limit(0);
	error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
/*
	Author: Aditya
	Date & Time: 24-03-2015
	Description: This file is used to query the database to get the required fields in the entry page*/
	include("config.php");	
	global $db_host,$db_name,$db_user,$db_password;
	$con=mysqli_connect($db_host,$db_user,$db_password,$db_name);
	$flag=1;	//flag is used to decide if profiles or tests or both are selected 1=>test 2=>profile 3=>profile and test

	if(!mysqli_connect_errno())
	{
		//to get centre details
		if(isset($_GET['centre']) && $_GET['centre']=="all" && !isset($_GET['checkcost']))
		{
			$centres=array();
			$query_select_centres="select * from centres";
			$centres_result = mysqli_query($con,$query_select_centres);
			while($row=mysqli_fetch_array($centres_result)){
				array_push($centres,array($row['SysNo'],$row['SysField']));
			}
			echo json_encode($centres);
		}
		
		//to get Profiles and tests details
		if(isset($_GET['profile']) && $_GET['profile']=="all" && !isset($_GET['checkcost']))
		{
			$profiles=array();
			$query_select_centres="select code,name from ProfilesAndTests where type='0' order by name";
			$centres_result = mysqli_query($con,$query_select_centres);
			while($row=mysqli_fetch_array($centres_result)){
				array_push($centres,array($row['code'],$row['name']));				
			}
			echo json_encode($centres);
		}
		
		if(isset($_GET['profilet']) && $_GET['profilet']=="all" && !isset($_GET['checkcost']))
		{
			$profiles=array();
			$query_select_centres="select code,name from ProfilesAndTests where type='1' order by name";
			$centres_result = mysqli_query($con,$query_select_centres);
			while($row=mysqli_fetch_array($centres_result)){
				array_push($profiles,array($row['code'],$row['name']));				
			}
			echo json_encode($profiles);
		}
		
		//to get templates
		if(isset($_GET['templates']) && $_GET['templates']=="usersmstemplates"){
			$query_SMSTemplates="select name,template from templates where templatetype=1 and userid=".$_SESSION['userid'];
			//echo $query_SMSTemplates;
			$SMSTemplates_result = mysqli_query($con,$query_SMSTemplates);
			$templates=array();
			$i=0;
			
			if(mysqli_num_rows($SMSTemplates_result)){
				while($row=mysqli_fetch_array($SMSTemplates_result)){
					$templates[$i][0]=$row[0];
					$templates[$i++][1]=$row[1];
				}	
				echo json_encode($templates);	
			}
			else{
				echo "";
			}
		}
		
		
		//to get test details based on profile selected
		if(isset($_GET['profile']) && $_GET['profile']!="all" && !isset($_GET['checkcost']))
		{
			$query_select_tests="";
			$tests_result = mysqli_query($con,$query_select_tests);
			echo '<option value="0">All</option>';
			while($row=mysqli_fetch_array($tests_result)){
				echo '<option value="'.$row['TestCode'].'">'.$row['TestPrintAs'].'</option>';
			}
		}
		
		if(isset($_GET['checkcost'])){
			$count=0;
			$pcount=substr_count($_GET['profile'], 'p');	//stores number of profiles from the string
			$ptcount=substr_count($_GET['profile'], ',');  //stores number of tests or profiles selected
		
			if($pcount==0)
				$flag=1;
			else if($pcount==$ptcount+1)
				$flag=2;
			else
				$flag=3;
				
			//echo "flag".$flag;	
			if($flag==1 || $flag ==2){				//if the selection contains either profiles or tests
				$ptstring=str_replace("p","",$_GET['profile']);
				$query_count_mobilenumbers=preparequery()." where ".preparewhere($ptstring);
				//echo "query::".$query_count_mobilenumbers;
				$mobile_result1 = mysqli_query($con,$query_count_mobilenumbers);
				while($row=mysqli_fetch_array($mobile_result1)){
					$mobile=$row['mobile'];
					if(phoneNumbervalidation($mobile))
						$count++;
				}
				//echo "any ones of count :".$count;
			}	
			else{									//if the selection contains both profiles and tests				
				
				$no_of_profiles=substr_count($_GET['profile'], 'p');
				$nth_comma_index=strpos_offset(',',$_GET['profile'], $no_of_profiles);	//gets the nth comma's index
				$pstring=str_replace("p","",substr($_GET['profile'],0,$nth_comma_index));
				$tstring=substr($_GET['profile'],$nth_comma_index+1);
				$mobile="";
				
				//query as tests
				$flag=1;							
				$query_count_mobilenumbers=preparequery()." where ".preparewhere($tstring);
				//echo $query_count_mobilenumbers;
				$mobile_result2 = mysqli_query($con,$query_count_mobilenumbers);
				while($row=mysqli_fetch_array($mobile_result2)){
					if(phoneNumbervalidation($row['mobile']))
						$mobile.=$row['mobile'].",";
				}
				//echo "first mobile:".$mobile;
				//query as tests
				$flag=2;							
				$query_count_mobilenumbers=preparequery()." where ".preparewhere($pstring);
				//echo $query_count_mobilenumbers;
				$mobile_result3 = mysqli_query($con,$query_count_mobilenumbers);
				while($row=mysqli_fetch_array($mobile_result3)){
					if(phoneNumbervalidation($row['mobile']))
						$mobile.=$row['mobile'].",";
				}

				//echo "\n\n\nsecond mobile:".$mobile;


				$mobilestr = implode(',',array_unique(explode(',', $mobile)));	//eliminate duplicates from a string
				$count=count(explode(",", $mobilestr));
				//echo "count :".$count;
				}
				//echo "total :".$count;
			$retval=array("persons"=>$count,"cost"=>$count*$smscredit_price*intval($_GET['noofsms']),"credits"=>$count*intval($_GET['noofsms']));
			echo json_encode($retval);
		}

		}
	else{
		echo "failed to connect to database";
	}
?>	
