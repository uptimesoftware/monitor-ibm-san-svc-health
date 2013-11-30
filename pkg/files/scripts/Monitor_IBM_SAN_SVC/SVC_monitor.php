<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
    
	// Operational Status - array 
	//		Unknown 0						Stopping 9
	//		Other 1 						Stopped 10
	//		OK 2 							In Service 11
	//		Degraded 3 						No Contact 12
	//		Stressed 4 						Lost Communication 13
	//		Predictive Failure 5 			Aborted 14
	//		Error 6 						Dormant 15
	//		Non-Recoverable Error 7 		Supporting Entity in Error 16	
	//		Starting 8         				Completed 17 
	//										Power Mode 18 
	
	$ok_op_status = array(2,11,17);  
	$warning_op_status = array(1,3,4,5,8,9,14,15,18);  
	$critical_op_status = array(6,7,10,12,13,16);  
	$unknown_op_status = array(0);
	$op_status = array('Unknown','Other','OK','Degraded','Stressed','Predictive Failure','Error','Non-Recoverable Error','Starting','Stopping','Stopped','In Service','No Contact','Lost Communication','Aborted','Dormant','Supporting Entity in Error','Completed','Power Mode'); 
	
	// Native Status 
	// 		Offline 0 
	//		Online 1 
	//		Pending 2 
	//		Adding 3 
	//		Deleting 4 
	//		Flushing 5
	
	$ok_nat_status = array(1,5);
	$warning_nat_status = array(2,3,4);
	$critical_nat_status = array(0);
	
	
	// Get Input Variables
	$user=getenv('UPTIME_CIM-USERNAME');	
    $password=getenv('UPTIME_CIM-PASSWORD');
    $ip=getenv('UPTIME_HOSTNAME');
	$port=getenv('UPTIME_CIM-PORT');
	
	
	//Controller
	$controllerFilterString=getenv('UPTIME_CONTROLLERFILTERSTRING');
	//None|Include|Exclude
	$controllerStatusFilter=getenv('UPTIME_CONTROLLERFILTEROPTION');
	//true|false
	$controllerStatusCheck=getenv('UPTIME_CONTROLLERSTATUSCHECK');
		
	//Volume
	$volumeFilterString=getenv('UPTIME_VOLUMEFILTERSTRING');
	$volumeStatusFilter=getenv('UPTIME_VOLUMEFILTEROPTION');
	$volumeStatusCheck=getenv('UPTIME_VOLUMESTATUSCHECK');

	//FC
	$fcFilterString=getenv('UPTIME_FCFILTERSTRING');
	$fcStatusFilter=getenv('UPTIME_FCFILTEROPTION');
	$fcStatusCheck=getenv('UPTIME_FCSTATUSCHECK');	
	
	//Node
	$nodeFilterString=getenv('UPTIME_NODEFILTERSTRING');
	$nodeStatusFilter=getenv('UPTIME_NODEFILTEROPTION');
	$nodeStatusCheck=getenv('UPTIME_NODESTATUSCHECK');	
	
	//VDisk
	$vdiskFilterString=getenv('UPTIME_VDISKFILTERSTRING');
	$vdiskStatusFilter=getenv('UPTIME_VDISKFILTEROPTION');
	$vdiskStatusCheck=getenv('UPTIME_VDISKSTATUSCHECK');	
	
	//Host
	$hostFilterString=getenv('UPTIME_HOSTFILTERSTRING');
	$hostStatusFilter=getenv('UPTIME_HOSTFILTEROPTION');
	$hostStatusCheck=getenv('UPTIME_HOSTSTATUSCHECK');
	
	//Storage Pool
	$spFilterString=getenv('UPTIME_SPFILTERSTRING');
	$spStatusFilter=getenv('UPTIME_SPFILTEROPTION');
	$spStatusCheck=getenv('UPTIME_SPSTATUSCHECK');
	
	
	
    if($user !='' && $password!='' && $ip!=''){
	
		// Backend Controller
        $cmd_str ="https://$user:$password@$ip:$port/root/ibm:IBMTSSVC_BackendController";    
        $result=shell_exec("wbemcli -noverify ei '$cmd_str'");    
        
		$controllerAlert = array();
		if ($controllerStatusFilter == "Include") {
			$tmpControllerIncludeList = explode(',', $controllerFilterString);
			$controllerIncludeList = array_map('trim', $tmpControllerIncludeList);
		}
		elseif($controllerStatusFilter == "Exclude") {
			$tmpControllerExludeList = explode(',', $controllerFilterString);
			$controllerExcludeList = array_map('trim', $tmpControllerExludeList);
		}
		
        $skuList = explode("\n",$result); 
 
		// Looping through each controller
        foreach ($skuList as $sys_each_value){
			if (!empty($sys_each_value)) {
				$num_sys= explode(',',$sys_each_value);
				$output='';
				foreach ($num_sys as $value){     
					if(preg_match('/^ElementName/i', trim($value))){         
						$controller = explode('=',$value);
						$bc_name = str_replace('"', '', $controller[1]);
					} 
					if(preg_match('/^OperationalStatus/i', trim($value))){
						$operation = explode('=',$value);
						if($operation[1] <19 && $operation[1] != '..' && $operation[1] != '0x8000..'){
							$opst  = $op_status[$operation[1]];
						}
						if($operation[1] == '..'){                    
							$opst  ='DMTF Reserved';                    
						}
						if($operation[1] == '0x8000..'){
							$opst  ='Vendor Reserved';                    
						} 
					}           
				}
				
				
				// No Filter
				if($controllerStatusFilter == "None") {
					echo $bc_name.".BCOperationalStatus ".$operation[1]."\n";
					$controllerAlert = checkOpStatus($bc_name,$operation[1],$op_status,$controllerAlert,$critical_op_status,$warning_op_status,$unknown_op_status);
				} 
				elseif ($controllerStatusFilter == "Include") {

					if (in_array($bc_name, $controllerIncludeList)) {
						echo $bc_name.".BCOperationalStatus ".$operation[1]."\n";

						$controllerAlert = checkOpStatus($bc_name,$operation[1],$op_status,$controllerAlert,$critical_op_status,$warning_op_status,$unknown_op_status);
						
						// Remove value from array so we know what we haven't found yet
						$indexFound = array_search($bc_name, $controllerIncludeList);
						unset($controllerIncludeList[$indexFound]);
						$controllerIncludeList = array_values($controllerIncludeList);
						
					}
				}
				elseif ($controllerStatusFilter == "Exclude") {
					if (!in_array($bc_name, $controllerExcludeList)) {
						echo $bc_name.".BCOperationalStatus ".$operation[1]."\n";
						$controllerAlert = checkOpStatus($bc_name,$operation[1],$op_status,$controllerAlert,$critical_op_status,$warning_op_status,$unknown_op_status);
					}
				}
			}
        }
		
				
		// Backend Volume 
		$cmd_str ="https://$user:$password@$ip:$port/root/ibm:IBMTSSVC_BackendVolume";    
        $result=shell_exec("wbemcli -noverify ei '$cmd_str'");    
		//$result=shell_exec("type testOutput.txt | findstr \"\/root\/ibm:IBMTSSVC_BackendVolume.CreationClassName\"");  

		$volumeAlert = array();
		if ($volumeStatusFilter == "Include") {
			$tmpVolumeIncludeList = explode(',', $volumeFilterString);
			$volumeIncludeList = array_map('trim', $tmpVolumeIncludeList);
		}
		elseif($volumeStatusFilter == "Exclude") {
			$tmpVolumeExludeList = explode(',', $volumeFilterString);
			$volumeExcludeList = array_map('trim', $tmpVolumeExludeList);
		}
		
        $skuList = explode("\n",$result); 
        foreach ($skuList as $sys_each_value){
            if (!empty($sys_each_value)) {
				$num_sys= explode(',',$sys_each_value);        
				$output='';
				foreach ($num_sys as $value){
					if(preg_match('/^ElementName/i', trim($value))){              
						$controller = explode('=',$value);
						$bc_name = str_replace('"', '', $controller[1]);
						//$output.="\nBackendVolume.$bc_name";
					} 
					if(preg_match('/^OperationalStatus/i', trim($value))){               
						$operation = explode('=',$value);
						if($operation[1] <19 && $operation[1] != '..' && $operation[1] != '0x8000..'){
							$opst  = $op_status[$operation[1]];
						}
						if($operation[1] == '..'){                    
							$opst  ='DMTF Reserved';                    
						}
						if($operation[1] == '0x8000..'){
							$opst  ='Vendor Reserved';                    
						} 
					}
				}
				
				
				//echo $bc_name.".BVOperationalStatus ".$operation[1]."\n";
				
				// No Filter
				if($volumeStatusFilter == "None") {
					echo $bc_name.".BVOperationalStatus ".$operation[1]."\n";
					$volumeAlert = checkOpStatus($bc_name,$operation[1],$op_status,$volumeAlert,$critical_op_status,$warning_op_status,$unknown_op_status);
				} 
				elseif ($volumeStatusFilter == "Include") {

					if (in_array($bc_name, $volumeIncludeList)) {
						echo $bc_name.".BVOperationalStatus ".$operation[1]."\n";

						$volumeAlert = checkOpStatus($bc_name,$operation[1],$op_status,$volumeAlert,$critical_op_status,$warning_op_status,$unknown_op_status);
						
						// Remove value from array so we know what we haven't found yet
						$indexFound = array_search($bc_name, $volumeIncludeList);
						unset($volumeIncludeList[$indexFound]);
						$volumeIncludeList = array_values($volumeIncludeList);
						
					}
				}
				elseif ($volumeStatusFilter == "Exclude") {
					if (!in_array($bc_name, $volumeExcludeList)) {
						echo $bc_name.".BVOperationalStatus ".$operation[1]."\n";
						$volumeAlert = checkOpStatus($bc_name,$operation[1],$op_status,$volumeAlert,$critical_op_status,$warning_op_status,$unknown_op_status);
					}
				}
			}
        }
		
		//FC Port
		$cmd_str ="https://$user:$password@$ip:$port/root/ibm:IBMTSSVC_FCPort";          
        $result=shell_exec("wbemcli -noverify ei '$cmd_str'");
        //$result=shell_exec("type IBMTSSVC_FCPort.txt");
		
		$fcAlert = array();
		if ($fcStatusFilter == "Include") {
			$tmpFCIncludeList = explode(',', $fcFilterString);
			$fcIncludeList = array_map('trim', $tmpFCIncludeList);
		}
		elseif($fcStatusFilter == "Exclude") {
			$tmpFCExludeList = explode(',', $fcFilterString);
			$fcExcludeList = array_map('trim', $tmpFCExludeList);
		}
		
        $skuList = explode("\n",$result); 
        foreach ($skuList as $sys_each_value){
			if (!empty($sys_each_value)) {
				$num_sys= explode(',',$sys_each_value);
				$output='';
				$output2='';
				foreach ($num_sys as $value){
					if(preg_match('/^OperationalStatus/i', trim($value))){               
						$operation = explode('=',$value);
						if($operation[1] <19 && $operation[1] != '..' && $operation[1] != '0x8000..'){
							$opst  = $op_status[$operation[1]];
						}
						if($operation[1] == '..'){                    
							$opst  ='DMTF Reserved';                    
						}
						if($operation[1] == '0x8000..'){
							$opst  ='Vendor Reserved';                    
						} 
						//$output2.='.OperationalStatus '."$opst\n";
					}
					if(preg_match('/^NodeName/i', trim($value))){               
						$node = explode('=',$value);
						$nodename = str_replace('"', '', $node[1]); 
						$output.="\n$nodename".'.PortID ';
						$output2.="$nodename";
					}
					if(preg_match('/^PortID/i', trim($value))){              
						$fc_port_id = explode('=',$value);
						$output.="$port[1]\n";                               
					}  
				}
				
				
				//echo $nodename."_".$fc_port_id[1].".FCOperationalStatus ".$operation[1]."\n";
				
				// No Filter
				if($fcStatusFilter == "None") {
					echo $nodename."_".$fc_port_id[1].".FCOperationalStatus ".$operation[1]."\n";
					$fcAlert = checkOpStatus($nodename."_".$fc_port_id[1],$operation[1],$op_status,$fcAlert,$critical_op_status,$warning_op_status,$unknown_op_status);
				} 
				elseif ($fcStatusFilter == "Include") {

					if (in_array($nodename."_".$fc_port_id[1], $fcIncludeList)) {
						echo $nodename."_".$fc_port_id[1].".FCOperationalStatus ".$operation[1]."\n";

						$fcAlert = checkOpStatus($nodename."_".$fc_port_id[1],$operation[1],$op_status,$fcAlert,$critical_op_status,$warning_op_status,$unknown_op_status);
						
						// Remove value from array so we know what we haven't found yet
						$indexFound = array_search($nodename."_".$fc_port_id[1], $fcIncludeList);
						unset($fcIncludeList[$indexFound]);
						$fcIncludeList = array_values($fcIncludeList);
						
					}
				}
				elseif ($fcStatusFilter == "Exclude") {
					if (!in_array($nodename."_".$fc_port_id[1], $fcExcludeList)) {
						echo $nodename."_".$fc_port_id[1].".FCOperationalStatus ".$operation[1]."\n";
						$fcAlert = checkOpStatus($nodename."_".$fc_port_id[1],$operation[1],$op_status,$fcAlert,$critical_op_status,$warning_op_status,$unknown_op_status);
					}
				}
			}
        }
		
		//Node
		$cmd_str ="https://$user:$password@$ip:$port/root/ibm:IBMTSSVC_Node";       
        $result=shell_exec("wbemcli -noverify ei '$cmd_str'");   
        //$result=shell_exec("type testOutput.txt | findstr \"ibm:IBMTSSVC_Node\"");   

		$nodeAlert = array();
		if ($nodeStatusFilter == "Include") {
			$tmpNodeIncludeList = explode(',', $nodeFilterString);
			$nodeIncludeList = array_map('trim', $tmpNodeIncludeList);
		}
		elseif($nodeStatusFilter == "Exclude") {
			$tmpNodeExludeList = explode(',', $nodeFilterString);
			$nodeExcludeList = array_map('trim', $tmpNodeExludeList);
		}
		
        $skuList = explode("\n",$result);
        $nat_status = array('Offline','Online','Pending','Adding','Deleting','Flushing');  
        foreach ($skuList as $sys_each_value){    
            if (!empty($sys_each_value)) {
				$num_sys= explode(',',$sys_each_value);        
				$output='';
				foreach ($num_sys as $value){            
					if(preg_match('/^Caption/i', trim($value))){              
						$caption = explode('=',$value);
						$apps_temp = str_replace('"', '', $caption[1]);              
					} 
					if(preg_match('/^NativeStatus/i', trim($value))){               
						$native = explode('=',$value);
						$natst  = $nat_status[$native[1]];                
						$output ="$apps_temp".'.NativeStatus '."$natst";
					}          
				}
				
				//echo $apps_temp.".NodeStatus ".$native[1]."\n";
				
				// No Filter
				if($nodeStatusFilter == "None") {
					echo $apps_temp.".NodeStatus ".$native[1]."\n";
					$nodeAlert = checkNativeStatus($apps_temp,$native[1],$nat_status,$nodeAlert,$critical_nat_status,$warning_nat_status);
				} 
				elseif ($nodeStatusFilter == "Include") {

					if (in_array($apps_temp, $nodeIncludeList)) {
						echo $apps_temp.".NodeStatus ".$native[1]."\n";

						$nodeAlert = checkNativeStatus($apps_temp,$native[1],$nat_status,$nodeAlert,$critical_nat_status,$warning_nat_status);
						
						// Remove value from array so we know what we haven't found yet
						$indexFound = array_search($apps_temp, $nodeIncludeList);
						unset($nodeIncludeList[$indexFound]);
						$nodeIncludeList = array_values($nodeIncludeList);
						
					}
				}
				elseif ($nodeStatusFilter == "Exclude") {
					if (!in_array($apps_temp, $nodeExcludeList)) {
						echo $apps_temp.".NodeStatus ".$native[1]."\n";
						$nodeAlert = checkNativeStatus($apps_temp,$native[1],$nat_status,$nodeAlert,$critical_nat_status,$warning_nat_status);
					}
				}
				
				
			}
        }
		
		
		// Vdisk Status
		$cmd_str ="https://$user:$password@$ip:$port/root/ibm:IBMTSSVC_StorageVolume";    
        $result=shell_exec("wbemcli -noverify ei '$cmd_str'");    
		//$result=shell_exec("type testOutput.txt | findstr \"\/root\/ibm:IBMTSSVC_StorageVolume.CreationClassName\"");  

		$vdiskAlert = array();
		if ($vdiskStatusFilter == "Include") {
			$tmpVDiskIncludeList = explode(',', $vdiskFilterString);
			$vdiskIncludeList = array_map('trim', $tmpVDiskIncludeList);
		}
		elseif($vdiskStatusFilter == "Exclude") {
			$tmpVDiskExludeList = explode(',', $vdiskFilterString);
			$vdiskExcludeList = array_map('trim', $tmpVDiskExludeList);
		}
		
        $skuList = explode("\n",$result);
        foreach ($skuList as $sys_each_value){
			if (!empty($sys_each_value)) {
				$num_sys= explode(',',$sys_each_value);        
				$output='';
				foreach ($num_sys as $value){            
					if(preg_match('/^Caption/i', trim($value))){              
						$caption = explode('=',$value);
						$apps_temp = str_replace('"', '', $caption[1]);              
					} 
					if(preg_match('/^NativeStatus/i', trim($value))){               
						$native = explode('=',$value);
						$natst  = $nat_status[$native[1]];                
						$output ="$apps_temp".'.NativeStatus '."$natst\n";
					}
					if(preg_match('/^ElementName/i', trim($value))){                
						$pool = explode('=',$value);
						$elementName = str_replace('"', '', $pool[1]);
					}
					if(preg_match('/^OperationalStatus/i', trim($value))){
						$operation = explode('=',$value);
						if($operation[1] <19 && $operation[1] != '..' && $operation[1] != '0x8000..'){
							$opst  = $op_status[$operation[1]];
						}
						if($operation[1] == '..'){                    
							$opst  ='DMTF Reserved';                    
						}
						if($operation[1] == '0x8000..'){
							$opst  ='Vendor Reserved';                    
						} 
					}
				}

				
				//echo $elementName.".VDOperationalStatus ".$operation[1]."\n";
				
				// No Filter
				if($vdiskStatusFilter == "None") {
					echo $elementName.".VDOperationalStatus ".$operation[1]."\n";
					$vdiskAlert = checkOpStatus($elementName,$operation[1],$op_status,$vdiskAlert,$critical_op_status,$warning_op_status,$unknown_op_status);
				} 
				elseif ($vdiskStatusFilter == "Include") {

					if (in_array($elementName, $vdiskIncludeList)) {
						echo $elementName.".VDOperationalStatus ".$operation[1]."\n";

						$vdiskAlert = checkOpStatus($elementName,$operation[1],$op_status,$vdiskAlert,$critical_op_status,$warning_op_status,$unknown_op_status);
						
						// Remove value from array so we know what we haven't found yet
						$indexFound = array_search($elementName, $vdiskIncludeList);
						unset($vdiskIncludeList[$indexFound]);
						$vdiskIncludeList = array_values($vdiskIncludeList);
						
					}
				}
				elseif ($vdiskStatusFilter == "Exclude") {//plui
					if (!in_array($elementName, $vdiskExcludeList)) {
						echo $elementName.".VDOperationalStatus ".$operation[1]."\n";
						$vdiskAlert = checkOpStatus($elementName,$operation[1],$op_status,$vdiskAlert,$critical_op_status,$warning_op_status,$unknown_op_status);
					}
				}
			}
        }
		
		// Storage Pool Capacity
		$cmd_str ="https://$user:$password@$ip:$port/root/ibm:IBMTSSVC_ConcreteStoragePool";    
        $result=shell_exec("wbemcli -noverify ei '$cmd_str'");    
		//$result=shell_exec("type testOutput.txt | findstr \"IBMTSSVC_ConcreteStoragePool.CreationClassName\"");  

		
		$spAlert = array();
		if ($spStatusFilter == "Include") {
			$tmpSPIncludeList = explode(',', $spFilterString);
			$spIncludeList = array_map('trim', $tmpSPIncludeList);
		}
		elseif($spStatusFilter == "Exclude") {
			$tmpSPExludeList = explode(',', $spFilterString);
			$spExcludeList = array_map('trim', $tmpSPExludeList);
		};	
		
        $skuList = explode("\n",$result);

        foreach ($skuList as $sys_each_value){    
            if (!empty($sys_each_value)) {
				$num_sys= explode(',',$sys_each_value);       		
				foreach ($num_sys as $value){            
					if(preg_match('/^Overallocation/i', trim($value))){
						$Oa_name = explode('=',str_replace('"', '',trim($value)));
						$overall_allocation = $Oa_name[1];
					}
					if(preg_match('/^TotalManagedSpace/i', trim($value))){
						$tms_name = explode('=',str_replace('"', '',trim($value)));
						// Converting to TB
						$total_managed_space = $tms_name[1] / (1024 * 1024 * 1024 * 1024 ) ;
						$total_managed_space = round($total_managed_space, 1);
					}
					if(preg_match('/^RemainingManagedSpace/i', trim($value))){
						$rms_name = explode('=',str_replace('"', '',trim($value)));
						// Converting to TB
						$remaining_managed_space = $rms_name[1] / (1024 * 1024 * 1024 * 1024); 
						$remaining_managed_space = round($remaining_managed_space, 1);
					}
					if(preg_match('/^UsedCapacity/i', trim($value))){
						$uc_name = explode('=',str_replace('"', '',trim($value)));
						// Converting to TB
						$used_capacity = $uc_name[1] / (1024 * 1024 * 1024 * 1024); 
						$used_capacity = round($used_capacity, 1);
					}
					if(preg_match('/^Caption/i', trim($value))){              
						$caption = explode('=',$value);
						$apps_temp = str_replace('"', '', $caption[1]);              
					}
					if(preg_match('/^OperationalStatus/i', trim($value))){
						$operation = explode('=',$value);
						if($operation[1] <19 && $operation[1] != '..' && $operation[1] != '0x8000..'){
							$opst  = $op_status[$operation[1]];
						}
						if($operation[1] == '..'){                    
							$opst  ='DMTF Reserved';                    
						}
						if($operation[1] == '0x8000..'){
							$opst  ='Vendor Reserved';                    
						} 
					}
				}
				
				/*
				echo $apps_temp.".SPOperationalStatus ".$operation[1]."\n";
				echo $apps_temp.".SPOverall ".$overall_allocation."\n";
				echo $apps_temp.".SPTotal ".$total_managed_space."\n";
				echo $apps_temp.".SPRemaining ".$remaining_managed_space."\n";
				echo $apps_temp.".SPUsed ".$used_capacity."\n";
				*/
				
				// No Filter
				if($spStatusFilter == "None") {
					echo $apps_temp.".SPOperationalStatus ".$operation[1]."\n";
					echo $apps_temp.".SPOverall ".$overall_allocation."\n";
					echo $apps_temp.".SPTotal ".$total_managed_space."\n";
					echo $apps_temp.".SPRemaining ".$remaining_managed_space."\n";
					echo $apps_temp.".SPUsed ".$used_capacity."\n";
					$spAlert = checkOpStatus($apps_temp,$operation[1],$op_status,$spAlert,$critical_op_status,$warning_op_status,$unknown_op_status);
				} 
				elseif ($spStatusFilter == "Include") {

					if (in_array($apps_temp, $spIncludeList)) {
						echo $apps_temp.".SPOperationalStatus ".$operation[1]."\n";
						echo $apps_temp.".SPOverall ".$overall_allocation."\n";
						echo $apps_temp.".SPTotal ".$total_managed_space."\n";
						echo $apps_temp.".SPRemaining ".$remaining_managed_space."\n";
						echo $apps_temp.".SPUsed ".$used_capacity."\n";
						$spAlert = checkOpStatus($apps_temp,$operation[1],$op_status,$spAlert,$critical_op_status,$warning_op_status,$unknown_op_status);
						
						// Remove value from array so we know what we haven't found yet
						$indexFound = array_search($apps_temp, $spIncludeList);
						unset($spIncludeList[$indexFound]);
						$spIncludeList = array_values($spIncludeList);
						
					}
				}
				elseif ($spStatusFilter == "Exclude") {
					if (!in_array($apps_temp, $spExcludeList)) {
						echo $apps_temp.".SPOperationalStatus ".$operation[1]."\n";
						echo $apps_temp.".SPOverall ".$overall_allocation."\n";
						echo $apps_temp.".SPTotal ".$total_managed_space."\n";
						echo $apps_temp.".SPRemaining ".$remaining_managed_space."\n";
						echo $apps_temp.".SPUsed ".$used_capacity."\n";
						$spAlert = checkOpStatus($apps_temp,$operation[1],$op_status,$spAlert,$critical_op_status,$warning_op_status,$unknown_op_status);
					}
				}
				
			}
		}
		
		
		// Host Status
		//   Get Host Names
		$cmd_str ="https://$user:$password@$ip:$port/root/ibm:IBMTSSVC_ProtocolController";    
        $result=shell_exec("wbemcli -noverify ei '$cmd_str'");    
		//$result=shell_exec("type testOutput.txt | findstr \"IBMTSSVC_ProtocolController\"");  
		
		$hostAlert = array();
		if ($hostAlert == "Include") {
			$tmpHostIncludeList = explode(',', $hostFilterString);
			$hostIncludeList = array_map('trim', $tmpHostIncludeList);
		}
		elseif($hostAlert == "Exclude") {
			$tmpHostExludeList = explode(',', $hostFilterString);
			$hostExcludeList = array_map('trim', $tmpHostExludeList);
		}
		
        $skuList = explode("\n",$result);

		//   Get Host Status
		$host_status_cmd_str ="https://$user:$password@$ip:$port/root/ibm:IBMTSSVC_StorageHardwareID";
		$host_status_result=shell_exec("wbemcli -noverify ei '$host_status_cmd_str'");    
		//$host_status_result=shell_exec("type testOutput.txt | findstr \"IBMTSSVC_StorageHardwareID\"");  
		//Debug
		//$host_status_result=shell_exec("type StorageHardwareID.txt");		
		$hostStatusList = explode("\n",$host_status_result);
				
		// Going through all hosts
        foreach ($skuList as $sys_each_value){
			$found_device_id = false;
			
            if (!empty($sys_each_value)) {
				$num_sys= explode(',',$sys_each_value);       		
				$output="";
				// Going through all properties for one host
				foreach ($num_sys as $value){
					if(preg_match('/^ElementName/i', trim($value))){
						$temp_value = explode('=',str_replace('"', '',trim($value)));
					
						$host_name = $temp_value[1];
						//echo "hostname=$host_name\n";
						
					}
					if(preg_match('/^DeviceID/i', trim($value))){
						// Sample DeviceID: 0000020060216172:11,
						$temp_value = explode('=',str_replace('"', '',trim($value)));
						$device_id = $temp_value[1];
						// Sample InstanceID: 	IBMTSSVC:0000020060216172-11-21000024FF21FA9B
						//						IBMTSSVC:0000020060216172-11-21000024FF21FA9A
						$instance_id = str_replace(':','-',$device_id);
						$found_device_id = true;
					}
				}
				
				if($found_device_id) {
					$found_instance_id = false;
					
					// Going through each Host Status line
					// There could be multiple lines for the same host.  
					// Let's take the first one.
					foreach ($hostStatusList as $hostStatus) {
						if (!empty($hostStatus)) {
							$hostStatusProperties = explode(',',$hostStatus);
							// Going through each property
							foreach ($hostStatusProperties as $singleProperty){
								if(preg_match("/$instance_id/", trim($singleProperty))){
									$found_instance_id = true;
								}
								if ($found_instance_id && (preg_match("/^State/i", trim($singleProperty)))) {
									$temp_value = explode('=',$singleProperty);
									$host_status = str_replace('"', '',trim($temp_value[1]));

									// No Filter
									if($hostStatusFilter == "None") {
										if (preg_match("/active/i", trim($singleProperty))) {
											echo $host_name.".HostStatus 1\n";
										} else {
											echo $host_name.".HostStatus 0\n";
											$hostAlert[] = array($host_name, 2, "inactive");
										}
									} 
									elseif ($hostStatusFilter == "Include") {

										if (in_array($host_name, $hostIncludeList)) {
											
											if (preg_match("/active/i", trim($singleProperty))) {
												echo $host_name.".HostStatus 1\n";
											} else {
												echo $host_name.".HostStatus 0\n";
												$hostAlert[] = array($host_name, 2, "inactive");
											}
																				
											// Remove value from array so we know what we haven't found yet
											$indexFound = array_search($host_name, $hostIncludeList);
											unset($hostIncludeList[$indexFound]);
											$hostIncludeList = array_values($hostIncludeList);
											
										}
									}
									elseif ($hostStatusFilter == "Exclude") {
										if (!in_array($host_name, $hostExcludeList)) {
											if (preg_match("/active/i", trim($singleProperty))) {
												echo $host_name.".HostStatus 1\n";
											} else {
												echo $host_name.".HostStatus 0\n";
												$hostAlert[] = array($host_name, 2, "inactive");
											}
										}
									}

									break;
								}
							}
							if ($found_instance_id) {
								break;
							}
						}
					}
				}

			}
		}

		$highest_severity = 0;
		// Go through all the alert arrays and exit the script accordingly
		// Each item is an array where...
		// [0] - name
		// [1] - severity (i.e. 1 = Warning, 2 = Critical, 3 = Unknown)
		// [2] - status description
		foreach ($controllerAlert as $item) {
			if ($item[1] > $highest_severity ) {
				$highest_severity = $item[1];
			}
			echo $item[0] . " is in " . $item[2] . " state.\n";			
		}
		foreach ($volumeAlert as $item) {
			if ($item[1] > $highest_severity ) {
				$highest_severity = $item[1];
			}
			echo $item[0] . " is in " . $item[2] . " state.\n";			
		}
		foreach ($fcAlert as $item) {
			if ($item[1] > $highest_severity ) {
				$highest_severity = $item[1];
			}
			echo $item[0] . " is in " . $item[2] . " state.\n";			
		}
		foreach ($nodeAlert as $item) {
			if ($item[1] > $highest_severity ) {
				$highest_severity = $item[1];
			}
			echo $item[0] . " is in " . $item[2] . " state.\n";			
		}
		foreach ($vdiskAlert as $item) {
			if ($item[1] > $highest_severity ) {
				$highest_severity = $item[1];
			}
			echo $item[0] . " is in " . $item[2] . " state.\n";			
		}
		foreach ($spAlert as $item) {
			if ($item[1] > $highest_severity ) {
				$highest_severity = $item[1];
			}
			echo $item[0] . " is in " . $item[2] . " state.\n";			
		}
		foreach ($hostAlert as $item) {
			if ($item[1] > $highest_severity ) {
				$highest_severity = $item[1];
			}
			echo $item[0] . " is in " . $item[2] . " state.\n";			
		}
		if ($highest_severity != 0) {
			exit($highest_severity);
		}
		
		// If the include list is not empty, this means we didn't find a specified item in the include list
		// Therefore, exit with error.
		if ((!empty($controllerIncludeList))||(!empty($volumeIncludeList))||(!empty($fcIncludeList))||(!empty($nodeIncludeList))||(!empty($vdiskIncludeList))||(!empty($spIncludeList))||(!empty($hostIncludeList)))   {
		
			$tmpArray = array_merge($controllerIncludeList, $volumeIncludeList, $fcIncludeList, $nodeIncludeList, $vdiskIncludeList, $spIncludeList, $hostIncludeList);
			$comma_separated = implode(",", $tmpArray);
			echo "The following were not found but were specified in the include filter: ". $comma_separated;
			exit(2);
		}
		
		
		
    }else{    
        echo "please enter the correct parameter Ex:- php xxxx.php admin admin_pass 000.000.000.000\n";
    }
	
	function checkOpStatus($name,$status,$status_text_array,$alertArray,$critical_array,$warning_array,$unknown_array) {
	
		if (in_array($status, $critical_array)) {
			$alertArray[] =  array($name, 2, $status_text_array[$status]);		
			return $alertArray;
		}
		elseif (in_array($status, $warning_array)) {
			$alertArray[] =  array($name, 1, $status_text_array[$status]);		
			return $alertArray;
		}
		elseif (in_array($status, $unknown_array)) {
			$alertArray[] =  array($name, 3, $status_text_array[$status]);		
			return $alertArray;
		} 
		else {
			return;
		}
	}
	
	function checkNativeStatus($name,$status,$status_text_array,$alertArray,$critical_array,$warning_array) {
	
		if (in_array($status, $critical_array)) {
			$alertArray[] =  array($name, 2, $status_text_array[$status]);		
			return $alertArray;
		}
		elseif (in_array($status, $warning_array)) {
			$alertArray[] =  array($name, 1, $status_text_array[$status]);		
			return $alertArray;
		}
		else {
			return;
		}
	}


?>