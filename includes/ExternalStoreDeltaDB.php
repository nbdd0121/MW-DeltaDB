<?php
class ExternalStoreDeltaDB extends ExternalStoreMedium {
	public function fetchFromURL( $url ) {
		list( $location, $hash ) = DeltaDB::fromStoreUrl( $url );
		return DeltaDB::get( $location, $hash );
	}

	public function store( $location, $data ) {
		$hash = DeltaDB::insert( $location, $data );
		return 'DeltaDB://' . $location . '/' . $hash;
	}

	public function isReadOnly( $location ) {
		return false;
	}
}
