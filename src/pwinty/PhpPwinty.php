<?php
/**
 * A PHP implementation of the Pwinty HTTP API  v2- http://www.pwinty.com/Api
 * Originally developed by Brad Pineau for Picisto.com. Updated to API Version 2 by Dan Huddart. Released to public under Creative Commons.
 *
 *
 * @author v1 Brad Pineau
 * @author v2 Dan Huddart
 * @author v2.1 Andy Wright
 * @see https://github.com/pwinty
 * @version 2.0
 * @access public
 *
 * based on the original version for Pwinty API v1 by Brad Pineau
 *
 * Usage:
 *
 * $options = array(
 *     'api'        => 'sandbox',
 *     'merchantId' => 'xxxxxxxxxxxxxxxxx',
 *     'apiKey'     => 'xxxxxxxxxxxxxxxxx'
 * );
 * $pwinty = new PHPPwinty($options);
 * $catalogue = $pwinty->getCatalogue('GB', 'Pro');
 *
 */

namespace pwinty;

class PhpPwinty {
	public $opt = array();
	public $api_url = "";
	public $last_error = "";

    /**
    * The class constructor
    *
    * @access private
    */
	function __construct($options) {
		$this->opt = $options;
		if ($this->opt['api'] == "production") {
			$this->api_url = "https://api.prodigi.com/v4.0";
		} else {
			$this->api_url = "https://api.sandbox.prodigi.com/v4.0";
		}
	}



    /**
    * Sends a HTTP request to the Pwinty API. This should not be called directly.
    *
    * @param string $call The API call.
    * @return array The response returned from the API call.
    * @access private
    */
	function apiCall($call, $data, $method) {
		/*
			internal function, you shouldn't call directly
		*/
		$url = $this->api_url.$call;
		if (($method != "POST") && ($method != "PUT")) {
			$url .= "?".http_build_query($data);
		}
		$headers = array();
		$headers[] = 'X-API-Key: '.$this->opt['apiKey'];
		// $headers[] = 'X-API-MerchantId: '.$this->opt['merchantId'];
		// $headers[] = 'X-Pwinty-REST-API-Key: '.$this->opt['apiKey'];
		$headers[] = 'Content-Type:application/json';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, true); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		if ($method == "POST") {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		} elseif ($method == "GET") {
			curl_setopt($ch, CURLOPT_POST, 0);
			curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		} elseif ($method == "PUT") {
	        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
	        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		} elseif ($method == "DELETE") {
			curl_setopt($ch, CURLOPT_PUT, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		}
		curl_setopt($ch, CURLOPT_FAILONERROR, 0); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
		curl_setopt($ch, CURLOPT_USERAGENT, "PHPPwinty v2");
		$result_text = curl_exec($ch);
		$curl_request_info = curl_getinfo($ch);
		curl_close($ch);
		if ($curl_request_info["http_code"] == 401) {
			$this->last_error = "Authorization unsuccessful. Check your Merchant ID and API key.";
			return array();
		}
		#echo $curl_request_info["http_code"] ;
		$data = json_decode($result_text, true);
		return $data;
	}

    /**
    * Creates a new order
    *
    * @access public
    */
	function createOrder($array_data) {
		$array_data = json_encode($array_data);
		$data = $this->apiCall("/Orders", $array_data, "POST");
		if (is_array($data)) {
			if (isset($data["errorMessage"])) {
				$this->last_error = $data["errorMessage"];
				return 0;
			} else {
				return $data;
			}
		} else {
			return 0;
		}
	}
    /**
    * Retrieves information about all your orders, or a specific order
    *
    * @param string $id the id of a specific order to retrieve information on (optional)
    * @return array The order details
    * @access public
    */
	function getOrder($id="") {
		$data = array();
		$data["id"] = $id;
		$data = $this->apiCall("/Orders", $data, "GET");
		if (is_array($data)) {
			if (isset($data["error"])) {
				$this->last_error = $data["error"];
				return 0;
			} else {
				return $data;
			}
		} else {
			return 0;
		}
	}
	/**
    * Updates an existing order
    *
    * @param string $id the id of the order to update
    * @param string $recipientName Who the order should be addressed to
    * @param string $address1 1st line of recipient address
    * @param string $address2 2nd line of recipient address (optional)
    * @param string $addressTownOrCity Town or City in the address
    * @param string $stateOrCounty State or County in the address
    * @param string $postalOrZipCode Postal code or Zip code of recipient
    * @return array The order details
    * @access public
    */
	function updateOrder($id, $recipientName, $address1, $address2, $addressTownOrCity, $stateOrCounty, $postalOrZipCode) {

		$data = array(
			"recipientName" => $recipientName,
			"address1" => $address1,
			"address2" => $address2,
			"addressTownOrCity" => $addressTownOrCity,
			"stateOrCounty" => $stateOrCounty,
			"postalOrZipCode" => $postalOrZipCode
		);

		$str_data = json_encode($data);

		$data = $this->apiCall("/Orders/".$id, $str_data, "PUT");
		
		if (is_array($data)) {
			if (isset($data["error"])) {
				$this->last_error = $data["error"];
				return 0;
			} else {
				return 1;
			}
		} else {
			return 0;
		}
	}
    /**
    * Update the status of an order
    *
    * @param string $id Order id
    * @param string $status Status to which the order should be updated
    * @return array The order details
    * @access public
    */
	function updateOrderStatus($id, $status) {
		$data = array(
			"status" => $status
		);

		$str_data = json_encode($data);

		$data = $this->apiCall("/Orders/".$id."/Status", $str_data, "POST");
		if (is_array($data)) {
			if (isset($data["error"])) {
				$this->last_error = $data["error"];
				return 0;
			} else {
				return $data;
			}
		} else {
			return 0;
		}
	}
    /**
    * Gets information on whether the order is ready for submission, and any errors or warnings associated with the order
    *
    * @param string $id Order id
    * @return array The order submission status
    * @access public
    */
	function getOrderStatus($id) {
		$data = array();

		$data = $this->apiCall("/Orders/".$id."/SubmissionStatus", $data, "GET");
		if (is_array($data)) {
			if (isset($data["error"])) {
				$this->last_error = $data["error"];
				return 0;
			} else {
				return $data;
			}
		} else {
			return 0;
		}
	}
    /**
    * Add a photo to an order
    *
    * @param string $orderId the id of the order the photo is being added to
    * @param string $type the type/size of photo (available photo types)
    * @param string $url the url from which we can download it
    * @param string $copies the number of copies of the photo to include in the order
    * @param string $sizing how the image should be resized when printing (resizing options)
    * @param string $priceToUser  the price (in cents/pence) you'd like to charge for each copy (only available if your payment option is InvoiceRecipient
    * @param string $md5Hash an md5Hash of the file which we'll check before processing
    * @param string $file if you have the image file, then make this request as a multipart/form-data with the file included
	* @return array The order submission status
    * @access public
    */
	function addPhoto($orderId, $type, $url, $copies, $sizing, $priceToUser = null, $md5Hash = null, $file = null) {
		$data = array(
			"type" => $type,
			"url" => $url,
			"copies" => $copies,
			"sizing" => $sizing
		);
		if ($priceToUser) $data['priceToUser'] = $priceToUser;
		if ($md5Hash) $data['md5Hash'] = $md5Hash;
		if ($file) $data['file'] = $file;

		$str_data = json_encode($data);

		$data = $this->apiCall("/Orders/".$orderId."/Photos", $str_data, "POST");
		if (is_array($data)) {
			if (isset($data["error"])) {
				$this->last_error = $data["error"];
				return 0;
			} else {
				return $data;
			}
		} else {
			return 0;
		}
	}
    /**
    * Add a photos to an order
    *
    * @param  string $orderId the id of the order the photo is being added to
    * @param  array  $images array of image data
	* @return array  The order submission status
    * @access public
    */
	function addPhotos($orderId, $images) {
		//$type, $url, $copies, $sizing, $priceToUser = null, $md5Hash = null, $file = null
		if (is_array($images)) {
			$str_data = json_encode($images);
			$data = $this->apiCall("/Orders/".$orderId."/Photos/Batch", $str_data, "POST");
			if (is_array($data)) {
				if (isset($data["error"])) {
					$this->last_error = $data["error"];
					return 0;
				} else {
					return $data;
				}
			} else {
				return 0;
			}
		}
		return 0;
	}
    /**
    * Retrieves information about the photos in an order, or a specific photo
    *
    * @param string $id the id of the order
    * @param string $photoid the id of the photo (optional)
	* @return array The photo details
    * @access public
    */
	function getPhotos($id,$photoid="") {
		$data = array();

		$data = $this->apiCall("/Orders/".$id."/Photos/".$photoid, $data, "GET");
		if (is_array($data)) {
			if (isset($data["error"])) {
				$this->last_error = $data["error"];
				return 0;
			} else {
				return $data;
			}
		} else {
			return 0;
		}
	}
    /**
    * Removes a specific photo from an order
    *
    * @param string $id the id of the order
    * @param string $photoid the id of the photo
	* @return string The status of the delete
    * @access public
    */
	function deletePhoto($id,$photoid) {
		$data = array();

		$data = $this->apiCall("/Orders/".$id."/Photos/".$photoid, $data, "DELETE");
		if (is_array($data)) {
			if (isset($data["error"])) {
				$this->last_error = $data["error"];
				return 0;
			} else {
				return $data;
			}
		} else {
			return 0;
		}
	}
    /**
    * Removes a specific document from an order
    *
    * @param string $id the id of the document
	* @return string The status of the delete
    * @access public
    */
	function deleteDocument($id) {
		$data = array();
		$data["id"] = $id;

		$data = $this->apiCall("/Documents", $data, "DELETE");
		if (is_array($data)) {
			if (isset($data["error"])) {
				$this->last_error = $data["error"];
				return 0;
			} else {
				return $data;
			}
		} else {
			return 0;
		}
	}
	/**
    * Retrieves information about the Catalogue
    *
    * @access public
    */
	function getCatalogue($countryCode,$qualityLevel) {

		$data = array();

		$data = $this->apiCall("/Catalogue"."/".$countryCode."/".$qualityLevel, $data , "GET");
		if (is_array($data)) {
			if (isset($data["error"])) {
				$this->last_error = $data["error"];
				return 0;
			} else {
				return $data;
			}
		} else {
			return 0;
		}
	}
	/**
    * Retrieves information about the Countries
    *
    * @access public
    */
	function getCountries() {

		$data = array();

		$data = $this->apiCall("/Country", $data , "GET");
		if (is_array($data)) {
			if (isset($data["error"])) {
				$this->last_error = $data["error"];
				return 0;
			} else {
				return $data;
			}
		} else {
			return 0;
		}
	}
}