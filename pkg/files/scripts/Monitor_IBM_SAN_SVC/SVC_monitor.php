<?php
    $user=getenv('UPTIME_CIM-USERNAME');	
    $password=getenv('UPTIME_CIM-PASSWORD');
    $ip=getenv('UPTIME_HOSTNAME');
	$port=getenv('UPTIME_CIM-PORT');

    if($user !='' && $password!='' && $ip!=''){    
	
		// Backend Controller
        $cmd_str ="https://$user:$password@$ip:$port/root/ibm:IBMTSSVC_BackendController";    
        
        $result=shell_exec("wbemcli -noverify ei '$cmd_str'");    
        $skuList = explode("\n",$result); 
        $op_status = array('','Other','OK','Degraded','Stressed','Predictive Failure','Error','Non-Recoverable Error','Starting','Stopping','Stopped','In Service','No Contact','Lost Communication','Aborted','Dormant','Supporting Entity in Error','Completed','Power Mode');  
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
				//echo $bc_name.".BCOperationalStatus ".$opst."\n";
				echo $bc_name.".BCOperationalStatus ".$operation[1]."\n";
			}
        }
		
		// Backend Volume 
		$cmd_str ="https://$user:$password@$ip:$port/root/ibm:IBMTSSVC_BackendVolume";    
        //$result=shell_exec("wbemcli ei -nl '$cmd_str'");    
        $result=shell_exec("wbemcli -noverify ei '$cmd_str'");    

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
				//echo $bc_name.".BVOperationalStatus ".$opst."\n";
				echo $bc_name.".BVOperationalStatus ".$operation[1]."\n";
			}
        }
		
		//FC Port
		$cmd_str ="https://$user:$password@$ip:$port/root/ibm:IBMTSSVC_FCPort";    
        //$result=shell_exec("wbemcli ei -nl '$cmd_str'");    
        $result=shell_exec("wbemcli -noverify ei '$cmd_str'");
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
				//echo $nodename."_".$fc_port_id[1].".FCOperationalStatus ".$opst."\n";
				echo $nodename."_".$fc_port_id[1].".FCOperationalStatus ".$operation[1]."\n";
			}
        }
		
		//Node
		$cmd_str ="https://$user:$password@$ip:$port/root/ibm:IBMTSSVC_Node";    
        //$result=shell_exec("wbemcli ei -nl '$cmd_str'");    
        $result=shell_exec("wbemcli -noverify ei '$cmd_str'");    
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
				//echo $apps_temp.".NodeStatus ".$natst."\n";
				echo $apps_temp.".NodeStatus ".$native[1]."\n";
			}
        }
		
		
		// Vdisk Status
		$cmd_str ="https://$user:$password@$ip:$port/root/ibm:IBMTSSVC_StorageVolume";    
        //$result=shell_exec("wbemcli ei -nl '$cmd_str'");    
        $result=shell_exec("wbemcli -noverify ei '$cmd_str'");    
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

				//echo $elementName.".VDOperationalStatus ".$opst."\n";
				echo $elementName.".VDOperationalStatus ".$operation[1]."\n";
			}
        }
		
		// Storage Pool Capacity
		$cmd_str ="https://$user:$password@$ip:$port/root/ibm:IBMTSSVC_ConcreteStoragePool";    
        $result=shell_exec("wbemcli -noverify ei '$cmd_str'");    
		//DEBUG
		//$result=shell_exec("type ConcreteStoragePool.txt");		
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
				}
				//echo "$output\n\n";
				echo $apps_temp.".SPOverall ".$overall_allocation."\n";
				echo $apps_temp.".SPTotal ".$total_managed_space."\n";
				echo $apps_temp.".SPRemaining ".$remaining_managed_space."\n";
				echo $apps_temp.".SPUsed ".$used_capacity."\n";
			}
		}
		
		
		// Host Status
		//   Get Host Names
		$cmd_str ="https://$user:$password@$ip:$port/root/ibm:IBMTSSVC_ProtocolController";    
        $result=shell_exec("wbemcli -noverify ei '$cmd_str'");    
		//$result=shell_exec("type ProtocolController.txt");
        $skuList = explode("\n",$result);

		//   Get Host Status
		$host_status_cmd_str ="https://$user:$password@$ip:$port/root/ibm:IBMTSSVC_StorageHardwareID";
		$host_status_result=shell_exec("wbemcli -noverify ei '$host_status_cmd_str'");    
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
									
									if (preg_match("/active/i", trim($singleProperty))) { 
										echo $host_name.".HostStatus 1\n";
									} else {
										echo $host_name.".HostStatus 0\n";
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
		
		
		
    }else{    
        echo "please enter the correct parameter Ex:- php xxxx.php admin admin_pass 000.000.000.000\n";
    }
?>