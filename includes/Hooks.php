<?php
use MediaWiki\Storage;

class DeltaDBHooks {

	public static function onRevisionRecordInserted( Storage\RevisionRecord $rev) {
		$parentId = $rev->getParentId();
		if ( !$parentId ) return;

		$revisionStore = MediaWiki\MediaWikiServices::getInstance()->getRevisionStore();
		$parentRev = $revisionStore->getRevisionById($parentId);

		list ( $location, $hash ) = self::getHashFromRevision( $rev );
		list ( $parentLocation, $parentHash ) = self::getHashFromRevision( $parentRev );
		if ( $location === null || $location !== $parentLocation) return;

		DeltaDB::link( $location, $hash, $parentHash );
	}

	private static function getHashFromRevision( Storage\RevisionRecord $rev) {
		$slot = $rev->getSlot( Storage\SlotRecord::MAIN, Storage\RevisionRecord::RAW );
		list( $schema , $textId ) = explode( ':', $slot->getAddress());
		if ( $schema !== 'tt') throw new MWException('Unknown schema');
		$textId = intval( $textId );

		$dbr = wfGetDB(DB_SLAVE);
		$row = $dbr->selectRow(
			'text',
			[ 'old_text', 'old_flags' ],
			[ 'old_id' => $textId ],
			__METHOD__
		);
		if ( !$row ) return null;
		$url = $row->old_text;
		$flags = $row->old_flags;
		if ( is_string( $flags ) ) {
			$flags = explode( ',', $flags );
		}
		// Not from external, so $url isn't actually an URL.
		if ( !in_array( 'external', $flags ) ) return null;
		return DeltaDB::fromStoreUrl( $url );
	}
}
