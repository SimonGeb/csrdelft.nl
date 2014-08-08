<?php

/**
 * ForumPost.class.php
 * 
 * @author P.W.G. Brussee <brussee@live.nl>
 * 
 * Een forumpost zit in een ForumDraad.
 * 
 */
class ForumPost extends PersistentEntity {

	/**
	 * Primary key
	 * @var int
	 */
	public $post_id;
	/**
	 * Deze post is van dit draadje
	 * @var int
	 */
	public $draad_id;
	/**
	 * Uid van auteur
	 * @var string
	 */
	public $lid_id;
	/**
	 * Tekst
	 * @var string
	 */
	public $tekst;
	/**
	 * Datum en tijd van aanmaken
	 * @var string
	 */
	public $datum_tijd;
	/**
	 * Datum en tijd van laatste bewerking
	 * @var string
	 */
	public $laatst_gewijzigd;
	/**
	 * Bewerking logboek
	 * @var string
	 */
	public $bewerkt_tekst;
	/**
	 * Verwijderd
	 * @var boolean
	 */
	public $verwijderd;
	/**
	 * IP adres van de auteur
	 * @var string
	 */
	public $auteur_ip;
	/**
	 * Wacht op goedkeuring
	 * @var boolean
	 */
	public $wacht_goedkeuring;
	/**
	 * Reden van wegfilteren
	 * @var string
	 */
	public $gefilterd;
	/**
	 * Database table fields
	 * @var array
	 */
	protected static $persistent_fields = array(
		'post_id'			 => array(T::Integer, false, 'auto_increment'),
		'draad_id'			 => array(T::Integer),
		'lid_id'			 => array(T::UID),
		'tekst'				 => array(T::Text),
		'datum_tijd'		 => array(T::DateTime),
		'laatst_gewijzigd'	 => array(T::DateTime),
		'bewerkt_tekst'		 => array(T::Text, true),
		'verwijderd'		 => array(T::Boolean),
		'auteur_ip'			 => array(T::String),
		'wacht_goedkeuring'	 => array(T::Boolean)
	);
	/**
	 * Database primary key
	 * @var array
	 */
	protected static $primary_keys = array('post_id');
	/**
	 * Database table name
	 * @var string
	 */
	protected static $table_name = 'forum_posts';

}
