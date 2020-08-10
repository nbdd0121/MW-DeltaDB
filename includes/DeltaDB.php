<?php
class DeltaDB {
	/**
	 * Convert an ExternalStore URL to location and hash
	 */
	public static function fromStoreUrl( $url ) {
		list ( $schema,, $location, $hash ) = explode( '/', $url );
		if ( $schema !== 'DeltaDB:' ) return [ null, null ];
		return [ $location, $hash ];
	}

	public static function apiUrlBase( $location ) {
		global $wgDeltaDBURL;
		if ( !isset( $wgDeltaDBURL[$location] ) ) throw new MWException ( "DeltaDB://$location is not configured" );
		return $wgDeltaDBURL[$location];
	}

	public static function get( $location, $hash ) {
		$url = self::apiUrlBase( $location ) . $hash;
		$content = Http::get( $url, [], __METHOD__ );
		if ( $content === false ) throw new MWException ( "Cannot get $hash from DeltaDB://$location" );
		return $content;
	}

	public static function insert( $location, $data ) {
		$url = self::apiUrlBase( $location );
		// CurlHttpRequest has a bug which doesn't send the body.
		$request = new PhpHttpRequest( $url, [
			'method' => 'PUT',
			'postData' => $data
		], __METHOD__ );
		$request->setHeader('Content-Type', 'text/plain; charset=utf-8');
		$status = $request->execute();
		if ( $status->isOK() ) {
			$hash = end ( explode( '/', $request->getResponseHeader('Location') ) );
			return $hash;
		} else {
			throw new MWException( $request->getContent() );
		}
	}

	public static function link( $location, $hash, $baseHash ) {
		$url = self::apiUrlBase ( $location ) . $hash . '?link=' . $baseHash;
		if (Http::request('PATCH', $url, [], __METHOD__ ) === false) throw new MWException( "Cannot link $hash and $baseHash at DeltaDB://$location" );
	}
}
