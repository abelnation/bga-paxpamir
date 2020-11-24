<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * paxpamir implementation : © Jeff DiCorpo <jdicorpo@gmail.com>
  *
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  *
  * paxpamir.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );
require_once ('modules/tokens.php');


class paxpamir extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        self::initGameStateLabels( array(
            "remaining_actions" => 10,

            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) );

        $this->tokens = new Tokens();
        // $this->tokens->initGlobalIndex('GINDEX', 0);

        // $this->cards = self::getNew( "module.common.deck" );
        // $this->cards->init("cards");

	}

    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "paxpamir";
    }

    /*
        setupNewGame:

        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        $this->tokens->createTokensPack("card_{INDEX}", "deck", 116);
        $this->tokens->shuffle("deck");

        // Init global values with their initial values
        self::setGameStateInitialValue( 'remaining_actions', 2 );

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        for ($i = 0; $i < 6; $i++) {
            $this->tokens->pickTokensForLocation(1, 'deck', 'market_0_'.$i);
            $this->tokens->pickTokensForLocation(1, 'deck', 'market_1_'.$i);
        }

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */

    protected function getAllDatas()
    {
        $result = array();

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );

        // TODO: Gather all information about current game situation (visible by player $current_player_id).

        $players = $this->loadPlayersBasicInfos();
        foreach ( $players as $player_id => $player_info ) {
            $result['court'][$player_id] = $this->tokens->getTokensInLocation('court_'.$player_id);
        }

        $result['token_types'] = $this->token_types;
        $result['cards'] = array();

        foreach ($this->token_types as $key => $value) {
            if ($this->startsWith($value['type'], 'card')) {
                $result['cards'][] = $key;
            }
        }
        $result['deck'] = $this->tokens->getTokensOfTypeInLocation(null, 'deck');
        for ($i = 0; $i < 6; $i++) {
            // $result['market'][0][$i] = $this->tokens->getTokenOnTop('market_0_'.$i);
            $result['market'][0][$i] = $this->tokens->getTokenOnLocation('market_0_'.$i);
            $result['market'][1][$i] = $this->tokens->getTokenOnLocation('market_1_'.$i);
        }

        $result['hand'] = $this->tokens->getTokensInLocation('hand_'.$current_player_id);

        return $result;
    }

    /*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with the "updateGameProgression" property set to true
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function startsWith ($string, $startString)
    {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in paxpamir.action.php)
    */


    function purchaseCard( $card_id )
    {
        self::checkAction( 'purchase' );

        $player_id = self::getActivePlayerId();
        $card = $this->tokens->getTokenInfo($card_id);
        $card_name = $this->token_types[$card_id]['name'];
        $market_location = $card['location'];

        if ($this->getGameStateValue("remaining_actions") > 0) {

            $this->tokens->moveToken($card_id, 'hand_'.$player_id);
            $this->incGameStateValue("remaining_actions", -1);

            self::notifyAllPlayers( "purchaseCard", clienttranslate( '${player_name} purchased ${card_name}' ), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'card' => $card,
                'card_name' => $card_name,
                'market_location' => $market_location,
                'i18n' => array( 'card_name' ),
            ) );

        }

        if ($this->getGameStateValue("remaining_actions") > 0) {
            $this->gamestate->nextState( 'action' );
        } else {
            $this->gamestate->nextState( 'clean_up' );
        }

    }

    function playCard( $card_id )
    {
        self::checkAction( 'play' );

        $player_id = self::getActivePlayerId();
        $card = $this->tokens->getTokenInfo($card_id);
        $card_name = $this->token_types[$card_id]['name'];

        if ($this->getGameStateValue("remaining_actions") > 0) {
            $this->tokens->moveToken($card_id, 'court_'.$player_id);
            $this->incGameStateValue("remaining_actions", -1);

            self::notifyAllPlayers( "playCard", clienttranslate( '${player_name} played ${card_name}' ), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'card' => $card,
                'card_name' => $card_name,
                'side' => 'left',
                'i18n' => array( 'card_name' ),
            ) );
        }

        if ($this->getGameStateValue("remaining_actions") > 0) {
            $this->gamestate->nextState( 'action' );
        } else {
            $this->gamestate->nextState( 'clean_up' );
        }

    }

    function passAction( )
    {
        self::checkAction( 'pass' );

        $player_id = self::getActivePlayerId();

        $remaining_actions = $this->getGameStateValue("remaining_actions");
        $state = $this->gamestate->state();

        if (($remaining_actions > 0) and ($state['name'] == 'playerActions')) {
            // self::incStat($remaining_actions, "skip", $player_id);
            $this->setGameStateValue("remaining_actions", 0);

            // Notify
            self::notifyAllPlayers( "passAction", clienttranslate( '${player_name} ended their turn.' ), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
            ) );
        }

        $this->gamestate->nextState( 'clean_up' );

    }

    /*

    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' );

        $player_id = self::getActivePlayerId();

        // Add your game logic to play a card there
        ...

        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );

    }

    */


//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argPlayerActions()
    {
        return array(
            'remaining_actions' => $this->getGameStateValue("remaining_actions"),
        );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    function stRefreshMarket()
    {
        $empty_top = array();
        $empty_bottom = array();
        $card_moves = array();
        $new_cards = array();

        for ($i = 0; $i < 6; $i++) {
            $from_location = 'market_0_'.$i;
            $card = $this->tokens->getTokenOnLocation($from_location);
            if ($card == null) {
                $empty_top[] = $i;
            } elseif (count($empty_top) > 0) {
                $to_locaction = 'market_0_'.array_shift($empty_top);
                $this->tokens->moveToken($card['key'], $to_locaction);
                $empty_top[] = $i;
                $card_moves[] = array(
                    'card_id' => $card['key'],
                    'from' => $from_location,
                    'to' => $to_locaction
                );

                self::notifyAllPlayers( "refreshMarket", '', array(
                    'card_moves' => $card_moves,
                    'new_cards' => $new_cards,
                ) );

                $this->gamestate->nextState( 'clean_up' );
                return;
            }

            $from_location = 'market_1_'.$i;
            $card = $this->tokens->getTokenOnLocation($from_location);
            if ($card == null) {
                $empty_bottom[] = $i;
            } elseif (count($empty_bottom) > 0) {
                $to_locaction = 'market_1_'.array_shift($empty_bottom);
                $this->tokens->moveToken($card['key'], $to_locaction );
                $empty_bottom[] = $i;
                $card_moves[] = array(
                    'card_id' => $card['key'],
                    'from' => $from_location,
                    'to' => $to_locaction
                );

                self::notifyAllPlayers( "refreshMarket", '', array(
                    'card_moves' => $card_moves,
                    'new_cards' => $new_cards,
                ) );

                $this->gamestate->nextState( 'clean_up' );
                return;
            }

        }

        foreach ($empty_top as $i) {
            $card = $this->tokens->pickTokensForLocation(1, 'deck', 'market_0_'.$i)[0];
            $new_cards[] = array(
                'card_id' => $card['key'],
                'from' => 'deck',
                'to' => 'market_0_'.$i
            );
        }

        foreach ($empty_bottom as $i) {
            $card = $this->tokens->pickTokensForLocation(1, 'deck', 'market_1_'.$i)[0];
            $new_cards[] = array(
                'card_id' => $card['key'],
                'from' => 'deck',
                'to' => 'market_1_'.$i
            );
        }

        self::notifyAllPlayers( "refreshMarket", clienttranslate( 'The market has been refreshed.' ), array(
            'card_moves' => $card_moves,
            'new_cards' => $new_cards,
        ) );

        $this->gamestate->nextState( 'next_turn' );

    }

    function stNextPlayer()
    {
        // Active next player
        $player_id = self::activeNextPlayer();

        $this->setGameStateValue("remaining_actions", 2);
        $this->giveExtraTime($player_id);

        $this->gamestate->nextState( 'next_turn' );

    }

    /*

    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...

        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:

        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).

        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message.
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];

        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );

            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }

///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }
}
