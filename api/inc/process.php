<?php

require_once('initialize.php');

/* 
Initialize Global array

Reason for the use of Array:
Processing speed is a lot faster than modifiing CSV data constantly. 
*/

$GLOBALS['z'] = array();

// Product Category Loop
for ($i=0; $i < sizeOf($product_categories); $i++) { 
	
	// Pass array value into Query Function
	$response = product_categories_items($product_categories[$i]['queryValue']);

	process_json ($response, $product_categories[$i]);
}

write_csv();



function product_categories_items($customQuery){

/*

Removed connection information - Private


*/ 

$curl = curl_init($API_Call);

curl_setopt($curl, CURLOPT_FAILONERROR, true);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$cresult = curl_exec($curl);


if(curl_error($curl)){ // if error occurs:

	echo curl_error($curl);
	
} else { // successful request:
	// echo "Connection Successful<br>";
	curl_close($curl);
	$ResponseObject = json_decode( $cresult);

	return $ResponseObject; 

}

}


function GenRandString($length = 16) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, strlen($characters) - 1)];
	}
	return $randomString;
}

// Error reporting if queried category returns an error
function process_json ($ResponseObject, $categoryArray) {


	if ($ResponseObject->response->record_count == ''){
		echo "<strong>Error</strong><br>";
		echo ".....................................................................<br>";
		echo "No products detected <br>";
		echo "Query Value: " . $categoryArray['queryValue'] . "<br>";
		echo "Parent Category: " . $categoryArray['parentCategory'] . "<br>";
		echo "Sub Category: " . $categoryArray['subCategory'] . "<br>";
		echo ".....................................................................<br><br>";

	} else {


// Loot through products
		foreach ($ResponseObject->response->records as $Item) {

			$searchValue = $Item->itemcode;

			$product_checker_return = search_array($searchValue);

			if (is_null($product_checker_return)){

				write_to_array($Item, $categoryArray);

			}
			else {
				// echo "Match Found:". $product_checker_return . "<br>";

				append_array($product_checker_return, $categoryArray);

			}
			
		}

	}

}

// Find Empty category slot and append the category
function append_array($product_checker_return, $categoryArray){

	$emptyCategory = findEmptyCategory($GLOBALS['z'][$product_checker_return]);
	$emptyMainCategory = $emptyCategory . " Main Category";
	$emptySubCategory = $emptyCategory . " Sub Category";

	$GLOBALS['z'][$product_checker_return][$emptyMainCategory] = $categoryArray['parentCategory'];

	$GLOBALS['z'][$product_checker_return][$emptySubCategory] = $categoryArray['subCategory'];


}


// Find Empty Category slot
function findEmptyCategory($product){


	if ($product['Secondary Main Category'] == ''){
		return 'Secondary';
	}
	else if ($product['Tertiary Main Category'] == ''){
		return 'Tertiary';
	}

	else if ($product['Quaternary Main Category'] == ''){
		return 'Quaternary';
	}

	else if ($product['Quinary Main Category'] == ''){
		return 'Quinary';
	}

}


// Search array for duplicate product
function search_array($searchvalue){

	foreach ($GLOBALS['z'] as $key => $val) {

		if ($val['Product_code'] === $searchvalue) {
			return $key;

		}
	}
	return null;


}


// Write value to array
function write_to_array($Item, $categoryArray){


	$arrayName = array(
		'Product_code' => $Item->itemcode, 
		'Image'=> $Item->img_xlrg_url, 
		'Main Category' => $categoryArray['parentCategory'],
		'Sub Category'=> $categoryArray['subCategory'], 
		'Product Name'=> $Item->description,
		'Material'=> $Item->material,
		'Color'=> $Item->color,
		'Secondary Main Category' => '', 
		'Secondary Sub Category'=> '',
		'Tertiary Main Category' => '', 
		'Tertiary Sub Category'=> '',
		'Quaternary Main Category' => '', 
		'Quaternary Sub Category'=> '',
		'Quinary Main Category' => '', 
		'Quinary Sub Category'=> '',
		'Brand' => $Item->db,
		'ID' => $Item->id,
		'Description' => $Item->notes

		);

	array_push($GLOBALS['z'], $arrayName);




}

// Build CSV
function write_csv(){

	$file = fopen('fields.csv', 'a');
	$arrayName = array();



	foreach ($GLOBALS['z'] as $item) {

		$arrayName = 
		array(
			$item["Product_code"], 
			$item["Image"], 
			$item["Main Category"],
			$item["Sub Category"],
			$item["Product Name"], 
			$item["Material"],
			$item["Color"],
			$item["Secondary Main Category"],
			$item["Secondary Sub Category"],
			$item["Tertiary Main Category"],
			$item["Tertiary Sub Category"],
			$item["Quaternary Main Category"],
			$item["Quaternary Sub Category"],
			$item["Quinary Main Category"],
			$item["Quinary Sub Category"],
			$item["Brand"],
			$item["ID"],
			$item["Description"]

			);


		fputcsv($file, $arrayName);
	}


}

?>	