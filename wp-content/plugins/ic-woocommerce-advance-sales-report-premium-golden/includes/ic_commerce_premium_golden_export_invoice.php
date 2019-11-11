<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'IC_Commerce_Premium_Golden_Export_Invoice' ) ) {
	require_once('ic_commerce_premium_golden_fuctions.php');
	class IC_Commerce_Premium_Golden_Export_Invoice extends IC_Commerce_Premium_Golden_Fuctions{
		
		public $constants 	=	array();
		
		public $states		= 	array();
		
		public $order_fee	= 	array();
		
		public $service_fee	= 	array();
		
		public function __construct($constants) {
			global $options;			
			$this->constants		= $constants;			
			$options				= $this->constants['plugin_options'];
			$this->invoice_dir		= "ic_woocommerce_pdfinvoice";
			$this->invoice_pre		= "invoice_";
			
		}//END __construct
		
		function invoice_action(){
			$order_id				= $this->get_request('order_id');
			$invoice_pre			= $this->get_request('pdfinvoice_invoice_pre',$this->invoice_pre,true);			
			$pdfinvoice_dir			= $this->get_request('pdfinvoice_dir',$this->invoice_dir,true);
			$bulk_action			= $this->get_request('bulk_action');
			
			$baseurl				= $this->get_request('baseurl',false);
			$basedir				= $this->get_request('basedir');
			
			if(!$baseurl){
				$upload_dir 		= wp_upload_dir(); // Array of key => value pairs
				$baseurl			= $this->get_request('baseurl',$upload_dir['baseurl'],true);
				$basedir			= $this->get_request('basedir',$upload_dir['basedir'],true);
				
				$baseurl 				= $this->get_request('baseurl',$baseurl, true);
				$basedir 				= $this->get_request('basedir',$basedir,true);
				
				
			}
			
			$file_name 				= $invoice_pre.$order_id.".pdf";
			$pdf_invoice_filedir	= $basedir."/".$pdfinvoice_dir."/".$file_name;			
			$pdf_invoice_filedir 	= $this->get_request('pdf_invoice_filedir',$pdf_invoice_filedir,true);
			
			
			$pdfinvoice_basedir = $basedir."/".$pdfinvoice_dir;
			
			$this->create_invoice_html();
			
			die;
		}
		
		
		
		function create_invoice_html(){
			$invoice_id = $this->get_request('invoice_id','0');					
				if($invoice_id > 0){
					
					$company_name	= $this->get_setting('company_name',$this->constants['plugin_options'], '');
					
					$out = "";
						
					$print_invoice 		= $this->get_request('print_invoice',0);
					
					if($print_invoice == 1){
						$out .= '<div class="back_print_botton noPrint">';	
						$out .= '	<input type="button" name="backtoprevious" value="Back to Previous"  class="onformprocess" onClick="back_to_detail();" />';	
						$out .= '	<input type="button" name="backtoprevious" value="Print"  class="onformprocess" onClick="print_report();" />';	
						$out .= '</div>';
					}
					
					$this->get_woocommerce_currency_symbol_pdf();
					
					$pdf_special_font = $this->get_pdf_special_font();
					
					$out .='<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"><html><head>
						<title>'.__('Invoice #','icwoocommerce_textdomains').$invoice_id.' | '.$company_name.'</title>
						<meta name="description" content="Invoice created by WooCommerce Sales Invoice Pro" />
						<meta name="keywords" content="Order Detail #'.$invoice_id.'" />
						<meta name="author" content="WooCommerce Sales Invoice Pro" />
						<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
						<style type="text/css"><!--
						'.$pdf_special_font.'
						.header {position: fixed; top: -40px; text-align:center;}
						.footer { position: fixed; bottom: 0px; text-align:center;}
						.pagenum:before{ content: counter(page); }
						
						body{font-family: "Source Sans Pro", sans-serif; font-size:12px; line-height:18px;}
						span{font-weight:bold;}
						.Clear, .clear{clear:both; margin-bottom:5px;}
						label{width:100px; float:left; }
						.sTable3{border:1px solid #DFDFDF;}
						.sTable3 th{
							padding:10px 10px 7px 10px;
							background:#eee url(../images/thead.png) repeat-x top left;
							text-align:left;
						}						
						.Form{padding:1% 1% 11% 1%; margin:5px 5px 5px 5px;}
						.myclass{border:1px solid black;}
							
						.sTable3 tbody tr td{padding:8px 10px; background:#fff; border-top:1px solid #DFDFDF; border-right:1px solid #DFDFDF;}
						.sTable3 tbody tr.AltRow td{background:#FBFBFB;}
						.sTable3 small{font-size:12px;}
						
						table.sTable1{margin-bottom:15px;}	
						table.sTable1 h3{margin-bottom:7px;}					
						table.sTable4{
								text-align:right; 
								margin-top:15px;
								margin-right:0px;
								width:100%;
						}
						.align_left{text-align:left;}
						.align_center{text-align:center;}
						.align_right{text-align:right;}
						
						.sTable4 td{text-align:left;}
						
						h3{padding-bottom:0;margin-bottom:0;}
						td.para p{line-height:18px; margin:auto; padding:0}
						
						.print_table{}
						.print_table h1{font-size:22px;}						
						.print_table p{font-size:12px; padding-bottom:0px;}
						.print_address{width:65%;}
						
						.note{padding:10px 0 10px 0;}';
						$print_invoice 		= $this->get_request('print_invoice',0);
						if($print_invoice == 0){
							//export css
							$out .='
							body{font-family: "Source Sans Pro", sans-serif; font-size:12px; line-height:18px;}
							h1, h2, h3, h4, h5, h6{margin:0; padding:0px;}
							p, .print_table p{padding-bottom:2px; margin:0px; font-size:12px;}
							.print_table h1{padding:5px 0 3px 0;}
							';
						}else{
							//print css
							$out .='';
						}
						
						$out .='-->
						</style>
						</head>
						<body class="invoice">';
				
						$invoice_id 			= $this->get_request('invoice_id','0');
						$baseurl 				= $this->get_request('baseurl');
						$basedir 				= $this->get_request('basedir');
						
						$order = new WC_Order ($invoice_id);
						
						$out .="<div class='footer'>".__('Page:','icwoocommerce_textdomains')." <span class='pagenum'></span></div>";
						
						$out .= "<div class='Container1'>";
						$out .= "<div class='Form1'>";
						$out .= "<div class='Clear'>";
						
						$header_output = $this->pdf_invoice_header($baseurl,$basedir);
						
						if(strlen($header_output)> 0){
							$out .= "<div class='Clear'></div>";
							$out .= "<div class='Clear'>";
							$out .= $header_output;
							$out .= "</div>";
						}
						
						$company_add1		= $this->get_setting('pdf_invoice_company_add1',$this->constants['plugin_options'], '');
						$company_add2		= $this->get_setting('pdf_invoice_company_add2',$this->constants['plugin_options'], '');
						
						$out .= "<table cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;\" class=\"print_table\">";
							$out .= "<tbody>";
								$out .= '<tr>';
									$out .= '<td class="print_address" valign="top">';
										if(strlen($header_output)> 0){
											$out .= "<p>".$company_add1."</p>";
											$out .= "<p>".$company_add2."</p>";
										}
									$out .= '</td>';
									$out .= '<td valign="top">';
										$out .= '<p><span class="strong">'.__('Invoice:','icwoocommerce_textdomains').' </span>'.$order->get_order_number().'</p>';
										
										$pdf_invoice_show_invoice_creatin_date		= $this->get_setting('pdf_invoice_show_invoice_creatin_date',$this->constants['plugin_options'], 0);
										
										if($pdf_invoice_show_invoice_creatin_date == 1){
											$out .= '<p><span class="strong">'.__('Date:','icwoocommerce_textdomains').' </span>'. date_i18n( get_option( 'date_format' ));
										}
										
										
									$out .= '</td>';
								$out .= '</tr>';
								
								$out .= '<tr>';
									$out .= '<td valign="top" colspan="2" class="note">';
										$pdf_invoice_show_header_note		= $this->get_setting('pdf_invoice_show_header_note',$this->constants['plugin_options'], 0);
										if($pdf_invoice_show_header_note == 1){
											$pdf_invoice_header_note		= $this->get_setting('pdf_invoice_header_note',$this->constants['plugin_options'], 0);
											if(strlen($pdf_invoice_header_note)>0){
												$out .= wpautop($pdf_invoice_header_note);
											}
										}
									$out .= '</td>';
								$out .= '</tr>';
							$out .= "</tbody>";
						$out .= "</table>";
						
						
				
						
						$out .= "<table class='sTable1 print_table' cellpadding='0' cellspacing='0'>";
							$out .= "<tbody>";
							
								$billing_address = "";								
								$billing_address = $order->get_formatted_billing_address();
								
								$shipping_address = "";
								if ( get_option( 'woocommerce_ship_to_billing_address_only' ) === 'no'):
									$shipping_address = $order->get_formatted_shipping_address();
								endif;
								
								if($billing_address || $shipping_address){
									$out .= '<tr>';								
										$out .= '<td valign="top">';
											$out .= '<h3>'.__('Billing address','icwoocommerce_textdomains').'</h3>';
											$out .= '<p class="billing_address">'.$billing_address.'</p>';
										$out .= '</td>';
										
										$out .= 	'<td> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </td>';
										
										$out .= '<td valign="top">';
											if(strlen($shipping_address)>0){
												$out .= '<h3>'.__('Shipping address','icwoocommerce_textdomains').'</h3>';
												$out .= '<p class="shipping_address">'.$shipping_address.'</p>';
											}
										$out .= '</td>';
									$out .= '</tr>';
								}
								
								$out .= '<tr>';								
									$out .= '<td valign="top" colspan="3" class="note">';
										$pdf_invoice_show_middle_note		= $this->get_setting('pdf_invoice_show_middle_note',$this->constants['plugin_options'], 0);
										if($pdf_invoice_show_middle_note == 1){
											$pdf_invoice_middle_note		= $this->get_setting('pdf_invoice_middle_note',$this->constants['plugin_options'], 0);
											if(strlen($pdf_invoice_middle_note)>0){
												$out .= wpautop($pdf_invoice_middle_note);
											}
										}
									$out .= '</td>';
								$out .= '</tr>';
							$out .= "</tbody>";
						$out .= "</table>";
						
						$currency_code_options = get_woocommerce_currencies();				
						
						$order_currency = get_post_meta($invoice_id,'_order_currency',true);
						$order_total  = get_post_meta($invoice_id,'_order_total',true);
						
						$woocommerce_currency_code = empty($order_currency) == false ? $order_currency : (get_post_meta($invoice_id, "_order_currency",true));
						$woocommerce_currency_code = strlen($woocommerce_currency_code) > 0 ? $woocommerce_currency_code : get_option('woocommerce_currency','USD');
						
						$woocommerce_currency_name =  isset($currency_code_options[$woocommerce_currency_code]) ? $currency_code_options[$woocommerce_currency_code] : '';
						
						$order_posts = $this->get_order_details($invoice_id);
						$order_status = isset($order_posts->post_status) ? $order_posts->post_status : '';
						
						
						
						$out .= "<div class='clear'></div>";
									
						$out .= "<table class='sTable3' cellpadding='0' cellspacing='0' width='100%'>\n";
						$out .= "<tbody>\n";
						$out .= '	<tr>'."\n";
						$out .= '		<th class="label">'.__('Product','icwoocommerce_textdomains').'</th>'."\n";
						$out .= '		<th style="width:50px; text-align:right;" class="label">'.__('Quantity','icwoocommerce_textdomains').'</th>'."\n";
						$out .= '		<th style="width:50px; text-align:right;" class="amount">'.__('Rate','icwoocommerce_textdomains').' ('.$woocommerce_currency_code.')</th>'."\n";
						$out .= '		<th style="width:100px; text-align:right;" class="amount">'.__('Amount','icwoocommerce_textdomains').'<br /> ('.$woocommerce_currency_code.')</th>'."\n";
						$out .= '	</tr>'."\n";
						
						$email_order_items_table = $this->email_order_items_table( $order->is_download_permitted(), true, ( $order_status =='wc-processing' ) ? true : false, $order);
												
						$email_order_items_table = str_replace("text-align:left; vertical-align:middle; border: 1px solid #eee; word-wrap:break-word;"," vertical-align:middle; text-align:left; word-wrap:break-word;",$email_order_items_table);
						$email_order_items_table = str_replace("text-align:left; vertical-align:middle; border: 1px solid #eee;","text-align:right; vertical-align:middle;",$email_order_items_table);
						$out .= $email_order_items_table;
						
						
						$out .= "</tbody>";
						$out .= "</table>";
						
						//$out .= "<div class='clear'></div>";
						$out .= "<div class='clear' style=\"width:370px; margin:auto; text-align:right;margin-right:0px;\">";
							$out .= "<table class='sTable3 sTable4' cellpadding='0' cellspacing='0' style=\"margin-top:0px;border-top:0\">";
							
							$out .= "<tbody>";
							if($totals = $order->get_order_item_totals()){
								$i = 0;
								foreach ( $totals as $total ) {
									$i++;
									$out .= '<tr>';
									$out .= '	<td style="width:87px;" class="label">';
									//$out .= 		'<strong>'.$total['label'].'</strong>';
									$out .= 		$total['label'];
									$out .= '	</td>';
									$out .= '	<td class="amount">';
									$out .= 		$total['value'];
									$out .= '	</td>';									
									$out .= '</tr>';
								}
							}
							$out .= "</tbody>";
							$out .= "</table>";
						$out .= "</div>";
						
						$out .= "<table cellpadding='0' cellspacing='0' width='100%' class=\"print_table\" style=\"padding-top:6px;\">";
							$out .= "<tbody>";
								$out .= '<tr>';
								$out .= '	<td colspan="2">';
								$out .= 	"<p style=\"font-size:13px;\" class=\"label\">".__('Amount in Words:','icwoocommerce_textdomains')." ". $woocommerce_currency_name ." ".$this->convertCurrencyToWords($order_total)."</p>";
								$out .= '	</td>';
								$out .= '</tr>';
								
								$out .= '<tr>';
								$out .= '<td>';
											$pdf_invoice_show_footer_note		= $this->get_setting('pdf_invoice_show_footer_note',$this->constants['plugin_options'], 0);
											if($pdf_invoice_show_footer_note == 1){
												$pdf_invoice_footer_note		= $this->get_setting('pdf_invoice_footer_note',$this->constants['plugin_options'], 0);
												if(strlen($pdf_invoice_footer_note)>0){
													//$out .= "<div class='clear'></div>";
													$out .= wpautop($pdf_invoice_footer_note);
													//$out .= "<div class='clear'></div>";
												}
											}
								$out .= '</td>';
								$out .= '<td valign="middle" class=\"align_right\">';
											$pdf_invoice_show_signature	= $this->get_setting('pdf_invoice_show_signature',$this->constants['plugin_options'], false);						
											if($pdf_invoice_show_signature){
												$pdf_invoice_signature 		= $this->get_setting('pdf_invoice_signature',$this->constants['plugin_options'], '');
												if($pdf_invoice_signature){
													$pdf_invoice_signature_align= $this->get_setting('pdf_invoice_signature_align',$this->constants['plugin_options'], false);
													if($print_invoice == 0){
														$pdf_invoice_signature		= str_replace($baseurl,$basedir,$pdf_invoice_signature);
													}
													$pdf_invoice_signature		= "<div class='align_".$pdf_invoice_signature_align."'><img src='".$pdf_invoice_signature."' alt='' /></div>";
													
													//$out .= "<div class='clear'></div>";
													$out .= "<div class='clear label'>";
													$out .= $pdf_invoice_signature;
													$out .= "</div>";
												}
											}
								$out .= '</td>';
								$out .= '</tr>';
							$out .= "</tbody>";
							$out .= "</table>";
						
						$out .= "</div></div></div>";
						$out .= "</body>";			
						$out .= "</html>";
						
						
						
						if($print_invoice == 1){
							$out .= '<div class="back_print_botton noPrint">';	
							$out .= '	<input type="button" name="backtoprevious" value="Back to Previous"  class="onformprocess" onClick="back_to_detail();" />';	
							$out .= '	<input type="button" name="backtoprevious" value="Print"  class="onformprocess" onClick="print_report();" />';	
							$out .= '</div>';
						}
						$bulk_action			= $this->get_request('bulk_action');
						
						if($bulk_action == 'pdf_invoice_print'){
							echo $out;die;
						}
						
						//echo $out;die;
						
						return $this->export_to_pdf_invoice($out);
				}
		}
		
		function get_order_details($order_id = 0){
			global $wpdb;			
			$sql = "SELECT * FROM $wpdb->posts AS shop_order WHERE ID = {$order_id}";
			$item = $wpdb->get_row($sql);
			
			return $item;
		}
		
		function export_to_pdf_invoice($output){
			
				$order_id					= $this->get_request('order_id');
				$pdfinvoice_invoice_pre		= $this->get_request('pdfinvoice_invoice_pre',"no");
				$file_name 					= $pdfinvoice_invoice_pre.$order_id.".pdf";			
				//$pdf_invoice_filedir		= $this->get_request('pdf_invoice_filedir');
								
				$orientation_pdf 			= $this->get_request('orientation_pdf',"portrait");				
				$paper_size 				= $this->get_request('paper_size',"letter");
				
				$bulk_action 				= $this->get_request('bulk_action');
			
				//echo $output;
				//die;
				
				
				//require_once("ic_commerce_ultimate_report_dompdf_config.inc.php");
				//$dompdf = new DOMPDF();	
				
				define("DOMPDF_UNICODE_ENABLED", true);
				$plugin_dir = $this->constants['plugin_dir'];
				$pdf_path 	= $plugin_dir.'/dompdf-master/dompdfinit.php';				
				include_once($pdf_path);

				$dompdf->set_paper($paper_size,$orientation_pdf);
				$dompdf->load_html($output);
				$dompdf->render();
				$dompdf->stream($file_name);
				
				return true;
	
		}
		
		function pdf_invoice_header($baseurl,$basedir){
			$header_align		= $this->get_setting('pdf_invoice_name_logo_align',$this->constants['plugin_options'], 0);
			$header_output 		= "";
			
			if($header_align != '0'){
				
				$compnay_logo 		= $this->get_setting('pdf_invoice_logo',$this->constants['plugin_options'], '');
				$company_name		= $this->get_setting('pdf_invoice_company_name',$this->constants['plugin_options'], '');
				$print_invoice 		= $this->get_request('print_invoice',0);
				
				
				if($print_invoice == 0){
					/*echo $baseurl;
					echo "<br />";
					echo $basedir;
					echo "<br />";					
					*/
					$compnay_logo		= str_replace($baseurl,$basedir,$compnay_logo);
					//echo "<br />";
				}
				
				if(strlen($company_name)> 0){
					if($header_align == 'company_name_left'){
						return $header_output 		= "<h1 style=\"text-align:left\">".$company_name."</h1>";
					}
					
					if($header_align == 'company_name_center'){
						return $header_output 		= "<h1 style=\"text-align:center\">".$company_name."</h1>";
					}
					
					if($header_align == 'company_name_right'){
						return $header_output 		= "<h1 style=\"text-align:right\">".$company_name."</h1>";
					}
				}
				
				if(strlen($compnay_logo)> 0){
					if($header_align == 'company_logo_left'){
						return $header_output		= "<div class='clear  align_left'><img src='".$compnay_logo."' alt='' /></div>";
					}
					
					if($header_align == 'company_logo_center'){
						return $header_output		= "<div class='clear  align_center'><img src='".$compnay_logo."' alt='' /></div>";
					}
					
					if($header_align == 'company_logo_right'){
						return $header_output		= "<div class='clear  align_right'><img src='".$compnay_logo."' alt='' /></div>";
					}
				}
				
				if(strlen($company_name) > 0 || strlen($compnay_logo) > 0){
				
					if($header_align == 'company_name_left_right'){
						$header_output		.= "<table cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%\">";
						$header_output		.= "<tr>";
						
						if(strlen($company_name) > 0){
							$header_output		.= "<td>";
							$header_output 		.= "<h1 style=\"text-align:left\">".$company_name."</h1>";
							$header_output		.= "</td>";
						}
						
						if(strlen($compnay_logo) > 0){
							$header_output		.= "<td>";
							
							if(strlen($company_name) > 0)
								$header_output		.= "<div class='clear  align_right'><img src='".$compnay_logo."' alt='' /></div>";
							else
								$header_output		.= "<div class='clear  align_left'><img src='".$compnay_logo."' alt='' /></div>";
								
							$header_output		.= "</td>";
						}
						
						$header_output		.= "</tr>";
						return $header_output		.= "</table>";
					}//company_name_left_right
					
					if($header_align == 'company_name_right_left'){
						$header_output		.= "<table cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%\">";
						$header_output		.= "<tr>";
						
						if(strlen($compnay_logo) > 0){
							$header_output		.= "<td>";
							$header_output 		.= "<div class='clear  align_left'><img src='".$compnay_logo."' alt='' /></div>";
							$header_output		.= "</td>";
						}
						
						if(strlen($company_name) > 0){
							$header_output		.= "<td>";
							
							if(strlen($compnay_logo) > 0)
								$header_output 		.= "<h1 style=\"text-align:right\">".$company_name."</h1>";
							else
								$header_output 		.= "<h1 style=\"text-align:left\">".$company_name."</h1>";
								
							$header_output		.= "</td>";
						}
						
						$header_output		.= "</tr>";
						return $header_output		.= "</table>";
					}//company_name_right_left
					
					//company_name_left_left
					if($header_align == 'company_name_left_left'){
						$header_output		.= "<table cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%\" class=\"print_table\">";
						$header_output		.= "<tr>";
						
						if(strlen($compnay_logo) > 0){
							$header_output		.= "<td>";
							$header_output 		.= "<div class='clear  align_left'><img src='".$compnay_logo."' alt='' /></div>";
							$header_output		.= "</td>";
							
						}else{
							if(strlen($company_name) > 0){
												
									$header_output		.= "<td colspan=\"2\">";
									
									$header_output 		.= "<h1 style=\"text-align:left\">".$company_name."</h1>";
										
									$header_output		.= "</td>";
								
							}else{
								$header_output		.= "<td>";							
								$header_output		.= "</td>";
							}
						}
						
						$header_output		.= "<td valign='middle'>";
						$header_output		.= "<h3 style=\"font-size:30px; font-weight:bold\">".__('INVOICE','icwoocommerce_textdomains')."</h3>";
						$header_output		.= "</td>";
						
						$header_output		.= "</tr>";
						
						
						
						
						if(strlen($company_name) > 0 and strlen($compnay_logo) > 0){
							$header_output		.= "<tr>";						
								$header_output		.= "<td colspan=\"2\">";
								
								$header_output 		.= "<h1 style=\"text-align:left\">".$company_name."</h1>";
									
								$header_output		.= "</td>";
							$header_output		.= "</tr>";
						}
						
						
						return $header_output		.= "</table>";
					}
				
					
					
					return $header_output;
				}//if(strlen($company_name) > 0 || strlen($compnay_logo) > 0){
				else{
					return $header_output;
				}
			}else{
				
				if(!$header_align){
					$header_output		.= "<table bgcolor=\"1\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%\" class=\"print_table\">";
					
					
					$company_name		= $this->get_setting('pdf_invoice_company_name',$this->constants['plugin_options'], '');	
					
					if(strlen($company_name) > 0){
						$header_output		.= "<tr>";						
							$header_output		.= "<td colspan=\"2\">";							
							$header_output 		.= "<h1 style=\"text-align:left\">".$company_name."</h1>";								
							$header_output		.= "</td>";
							
							$header_output		.= "<td valign='middle'>";
							$header_output		.= "<h3 style=\"font-size:30px; font-weight:bold\">".__('INVOICE','icwoocommerce_textdomains')."</h3>";
							$header_output		.= "</td>";
							
							
						$header_output		.= "</tr>";
					}else{
					
						$header_output		.= "<tr>";
						$header_output		.= "<td style=\"width:50%\">";							
						$header_output		.= "</td>";
						
						
						$header_output		.= "<td valign='middle'>";
						$header_output		.= "<h3 style=\"font-size:30px; font-weight:bold\">".__('INVOICE','icwoocommerce_textdomains')."</h3>";
						$header_output		.= "</td>";
						
						$header_output		.= "</tr>";
					
					
					}
					return $header_output		.= "</table>";
				}
			}
			
			return $header_output;
			
		}
		
		function email_order_items_table( $show_download_links = false, $show_sku = false, $show_purchase_note = false, $order = false, $show_image = false, $image_size = array( 32, 32 ), $plain_text = false) {
			
			$items = $order->get_items();

			ob_start();
			
			foreach ( $items as $item ) :
				$_product     = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );
				//$item_meta    = new WC_Order_Item_Meta( $item['item_meta'], $_product );
				$item_meta    = new WC_Order_Item_Product( $item['item_meta'], $_product );
				?> 
				<tr>
					<td style="text-align:left; vertical-align:middle; border: 1px solid #eee; word-wrap:break-word;" class="label"><?php
			
						// Show title/image etc
						if ( $show_image ) {
							echo apply_filters( 'woocommerce_order_item_thumbnail', '<img src="' . ( $_product->get_image_id() ? current( wp_get_attachment_image_src( $_product->get_image_id(), 'thumbnail') ) : wc_placeholder_img_src() ) .'" alt="' . __( 'Product Image', 'icwoocommerce_textdomains' ) . '" height="' . esc_attr( $image_size[1] ) . '" width="' . esc_attr( $image_size[0] ) . '" style="vertical-align:middle; margin-right: 10px;" />', $item );
						}
			
						// Product name
						echo apply_filters( 'woocommerce_order_item_name', $item['name'], $item );
			
						// SKU
						if ( $show_sku && is_object( $_product ) && $_product->get_sku() ) {
							echo ' (#' . $_product->get_sku() . ')';
						}
			
						// File URLs
						if ( $show_download_links && is_object( $_product ) && $_product->exists() && $_product->is_downloadable() ) {
							
							if(method_exists($order,'get_item_downloads')){
								$download_files = $order->get_item_downloads( $item );
								$i              = 0;
				
								foreach ( $download_files as $download_id => $file ) {
									$i++;
				
									if ( count( $download_files ) > 1 ) {
										$prefix = sprintf( __( 'Download %d', 'icwoocommerce_textdomains' ), $i );
									} elseif ( $i == 1 ) {
										$prefix = __( 'Download', 'icwoocommerce_textdomains' );
									}
				
									echo '<br/><small>' . $prefix . ': <a href="' . esc_url( $file['download_url'] ) . '" target="_blank">' . esc_html( $file['name'] ) . '</a></small>';
								}
							}//End method_exists
							
						}
			
						// Variation
						if(function_exists('wc_display_item_meta')){
							wc_display_item_meta( $item );
						}else{
							$meta = isset($item_meta->meta) ? $item_meta->meta : '';
							if ($meta){							
								echo '<br/><small>' . nl2br( $item_meta->display( true, true ) ) . '</small>';
							}
						}
					?></td>
					<td style="text-align:left; vertical-align:middle; border: 1px solid #eee;" class="label"><?php echo $item['qty'] ;?></td>
					<td style="text-align:left; vertical-align:middle; border: 1px solid #eee;" class="amount"><?php echo $item['line_total'] ;?></td>
					<td style="text-align:left; vertical-align:middle; border: 1px solid #eee;" class="amount"><?php echo $order->get_formatted_line_subtotal( $item ); ?></td>
				</tr>
			
				<?php if ( $show_purchase_note && is_object( $_product ) && $purchase_note = get_post_meta( $_product->get_id(), '_purchase_note', true ) ) : ?>
					<tr>
						<td colspan="3" style="text-align:left; vertical-align:middle; border: 1px solid #eee;"><?php echo apply_filters( 'the_content', $purchase_note ); ?></td>
					</tr>
				<?php endif; ?>
			 
			<?php endforeach; 
			$message = ob_get_clean();
			return $message;
		}
		
		function convertIntegerToWords($x){ 
			
			$x = $x + 0;
		
			$nwords = array( 
				__('zero','icwoocommerce_textdomains'), 
				__('one','icwoocommerce_textdomains'), 
				__('two','icwoocommerce_textdomains'),
				__('three','icwoocommerce_textdomains'),
				__('four','icwoocommerce_textdomains'), 
				__('five','icwoocommerce_textdomains'), 
				__('six','icwoocommerce_textdomains'),
				__('seven','icwoocommerce_textdomains'), 
				__('eight','icwoocommerce_textdomains'), 
				__('nine','icwoocommerce_textdomains'), 
				__('ten','icwoocommerce_textdomains'), 
				__('eleven','icwoocommerce_textdomains'), 
				__('twelve','icwoocommerce_textdomains'), 
				__('thirteen','icwoocommerce_textdomains'), 
				__('fourteen','icwoocommerce_textdomains'), 
				__('fifteen','icwoocommerce_textdomains'), 
				__('sixteen','icwoocommerce_textdomains'), 
				__('seventeen','icwoocommerce_textdomains'), 
				__('eighteen','icwoocommerce_textdomains'), 
				__('nineteen','icwoocommerce_textdomains'), 
				__('twenty','icwoocommerce_textdomains'), 
				30 => __('thirty','icwoocommerce_textdomains'), 
				40 => __('forty','icwoocommerce_textdomains'), 
				50 => __('fifty','icwoocommerce_textdomains'), 
				60 => __('sixty','icwoocommerce_textdomains'), 
				70 => __('seventy','icwoocommerce_textdomains'), 
				80 => __('eighty','icwoocommerce_textdomains'),
				90 => __('ninety','icwoocommerce_textdomains') 
			 );
			 
			 if(!is_numeric($x)) 
			 { 
				 $w = '#'; 
			 }else if(fmod($x, 1) != 0) 
			 { 
				 $w = '#'; 
			 }else{ 
				 if($x < 0) 
				 { 
					 $w = 'minus '; 
					 $x = -$x; 
				 }else{ 
					 $w = ''; 
				 } 
				 if($x < 21) 
				 { 
					 $w .= isset($nwords[$x]) ? $nwords[$x] : $x; 
				 }else if($x < 100) 
				 { 
					 $w .= $nwords[10 * floor($x/10)]; 
					 $r = fmod($x, 10); 
					 if($r > 0) 
					 { 
						 $w .= '-'. $nwords[$r]; 
					 } 
				 } else if($x < 1000) 
				 { 
					 $w .= $nwords[floor($x/100)] .' '.__('hundred','icwoocommerce_textdomains'); 
					 $r = fmod($x, 100); 
					 if($r > 0) 
					 { 
						 $w .= ' ' .__('and','icwoocommerce_textdomains').' '. $this->convertIntegerToWords($r); 
					 } 
				 } else if($x < 1000000) 
				 { 
					 $w .= $this->convertIntegerToWords(floor($x/1000)) .' '.__('thousand','icwoocommerce_textdomains'); 
					 $r = fmod($x, 1000); 
					 if($r > 0) 
					 { 
						 $w .= ' '; 
						 if($r < 100) 
						 { 
							 $w .= ' ' .__('and','icwoocommerce_textdomains').' '; 
						 } 
						 $w .= $this->convertIntegerToWords($r); 
					 } 
				 } else { 
					 $w .= $this->convertIntegerToWords(floor($x/1000000)) .' '.__('million','icwoocommerce_textdomains'); 
					 $r = fmod($x, 1000000); 
					 if($r > 0) 
					 { 
						 $w .= ' '; 
						 if($r < 100) 
						 { 
							 $word .= ' ' .__('and','icwoocommerce_textdomains').' '; 
						 } 
						 $w .= $this->convertIntegerToWords($r); 
					 } 
				 } 
			 } 
			 return $w; 
		}
		
		function convertCurrencyToWords($number)
		{
			$currencylabelsarray = array('dollars' => __('dollars','icwoocommerce_textdomains'), 'cents' => __('cents','icwoocommerce_textdomains'));
			if(!is_numeric($number)) return false;
			$nums = explode('.', $number);
			
			//$out = $this->convertIntegerToWords($nums[0]) . ' dollars';			
			$out = $this->convertIntegerToWords($nums[0]);
			
			if(isset($nums[1])) {
			$out .= ' ' .__('and','icwoocommerce_textdomains').' '. $this->convertIntegerToWords($nums[1]) .' '.__('cents','icwoocommerce_textdomains');
			}
			return $out;
		}
		
		
		
	}//End Class
}//End class_exists check