<?php


/**
 * Creates an initial deck, per the PaxPamir instructions.
 *
 * @param  int    $num_players
 * @return array<int>
 *         An ordered list of deck card numbers.
 *         Where the card at index 0 represents the last card to be drawn
 *         (i.e. the bottom of the deck).
 */
function getInitialDeck(int $num_players): array {
    // These ranges are based on the actual numbers on the PP cards
    $court_card_nums = range( 1, 100 );
    $dominance_check_card_nums = range( 101, 104 );
    $normal_event_card_nums = range( 105, 116 );

    // Pull sub-set of COURT cards that will be used for this game
    $court_cards_per_pile = (5 + $num_players);
    $num_court_cards = 6 * $court_cards_per_pile;
    $all_court_cards = array();
    foreach (array_rand($court_card_nums, $num_court_cards) as $key) {
        $card_num = $court_card_nums[$key];
        array_push($all_court_cards, $card_num);
    }

    // Pull sub-set of EVENT cards that will be used for this game
    $all_event_cards = array();
    foreach (array_rand($normal_event_card_nums, 6) as $key) {
        $card_num = $normal_event_card_nums[$key];
        array_push($all_event_cards, $card_num);
    }

    // Pull all dominance cards to be used for the game
    $all_dom_check_cards = array();
    foreach (array_rand($dominance_check_card_nums, count($dominance_check_card_nums)) as $key) {
        $card_num = $dominance_check_card_nums[$key];
        array_push($all_event_cards, $card_num);
    }

    # Split court cards into 6 even piles
    $court_chunks = array_chunk($all_court_cards, $court_cards_per_pile);

    $pile_1 = $court_chunks[0]; // will be the top of the draw pile
    shuffle($pile_1);

    # Pile 2 has two events, and no dominance cards
    $pile_2 = $court_chunks[1];
    array_push($pile_2, $all_event_cards[4]);
    array_push($pile_2, $all_event_cards[5]);
    shuffle($pile_2);

    # Piles 3-6 have one event, one dominance check
    $pile_3 = $court_chunks[2];
    array_push($pile_3, $all_event_cards[3]);
    array_push($pile_3, $all_dom_check_cards[3]);
    shuffle($pile_3);

    $pile_4 = $court_chunks[3];
    array_push($pile_4, $all_event_cards[2]);
    array_push($pile_4, $all_dom_check_cards[2]);
    shuffle($pile_4);

    $pile_5 = $court_chunks[4];
    array_push($pile_5, $all_event_cards[1]);
    array_push($pile_5, $all_dom_check_cards[1]);
    shuffle($pile_5);

    $pile_6 = $court_chunks[5]; // will be the bottom of the draw pile
    array_push($pile_6, $all_event_cards[0]);
    array_push($pile_6, $all_dom_check_cards[0]);
    shuffle($pile_6);

    // index zero is the "bottom" of the deck
    $deck = array_merge(
        $pile_6,
        $pile_5,
        $pile_4,
        $pile_3,
        $pile_2,
        $pile_1
    );

    return $deck;
}
