<?php

/*
  this requires PHP 5
  if you still run PHP 4, quit being lazy and upgrade
*/

$VERSION = '1.1';

function nickometer($nick) {
	global $score;

	echo '<h1>PHP Lame-o-Nickometer results</h1>';

	echo 'Original nick: '. $nick .'<br/>';

	$score = 0;

	# Deal with special cases (precede with \ to prevent de-k3wlt0k)
	$special_cost = array('69'		=> 500,
			'dea?th'		=> 500,
			'dark'			=> 400,
			'n[i1]ght'		=> 300,
			'n[i1]te'		=> 500,
			'fuck'			=> 500,
			'sh[i1]t'		=> 500,
			'coo[l1]'		=> 500,
			'kew[l1]'		=> 500,
			'lame'			=> 500,
			'dood'			=> 500,
			'dude'			=> 500,
			'rool[sz]'		=> 500,
			'rule[sz]'		=> 500,
			'[l1](oo?|u)[sz]er'	=> 500,
			'[l1]eet'		=> 500,
			'e[l1]ite'		=> 500,
			'[l1]ord'		=> 500,
			'k[i1]ng'		=> 500,
			'pron'			=> 1000,
			'warez'			=> 1000,
			'phi[1l]ip'		=> 1000,
			'xx'			=> 100,
			'\[rkx]0'		=> 1000,
			'\0[rkx]'		=> 1000,
			);

	foreach ($special_cost as $special_pattern => $value) {
		if (preg_match("/$special_pattern/i", $nick, $reg))
			punish($value, 'matched special case '.$reg[0]);
	}

  
	# Punish consecutive non-alphas
	preg_match_all("/([^A-Za-z]{2,})/", $nick, $regs);

	$consecutive = sizeof($regs[0]);
	if ($consecutive)
		punish(slow_pow(10, $consecutive), "$consecutive total consecutive non-alphas");

	# Remove one layer of balanced brackets and punish for rest
	# Porting to PHP made this messy
	#  ...room to improve here later
	if(strcmp(preg_replace ("/^([^{}]*)(\{)(.*)(\})([^{}]*)$/x", "\\1\\3\\5", $nick), $nick) != 0) {
		$newnick = preg_replace ("/^([^{}]*)(\{)(.*)(\})([^{}]*)$/x", "\\1\\3\\5", $nick);
		print 'Removed {} outside parentheses; nick now '. $newnick .'<br/>';
	} elseif(strcmp(preg_replace ("/^([^\[\]]*)(\[)(.*)(\])([^\[\]]*)$/x", "\\1\\3\\5", $nick), $nick) != 0) {
		$newnick = preg_replace ("/^([^\[\]]*)(\[)(.*)(\])([^\[\]]*)$/x", "\\1\\3\\5", $nick);
		print 'Removed [] outside parentheses; nick now '. $newnick .'<br/>';
	} elseif(strcmp(preg_replace ("/^([^()]*)(\()(.*)(\))([^()]*)$/", "\\1\\3\\5", $nick), $nick) != 0) {
		$newnick = preg_replace ("/^([^()]*)(\()(.*)(\))([^()]*)$/", "\\1\\3\\5", $nick);
		print 'Removed () outside parentheses; nick now '. $newnick .'<br/>';
	} else
		$newnick = $nick;


	// the following lines were done in perl with just: $parentheses = tr/(){}[]/(){}[]/;
	$parens = array('(',')','{','}','[',']');
	for($i=0; $i<sizeof($parens); $i++) {
		$parentheses += substr_count($newnick, $parens[$i]);
		$newnick = str_replace($parens[$i], '', $newnick);
	}

	if($parentheses)
		punish(slow_pow(10, $parentheses), "$parentheses extraneous "
		. ($parentheses == 1 ? 'parenthesis' : 'parentheses'));

	# Punish k3wlt0k
	$k3wlt0k_weights = array(5, 5, 2, 5, 2, 3, 1, 2, 2, 2);
	for($digit=0; $digit<=9; $digit++) {
		$occurrences = substr_count($newnick, $digit);

		if($occurrences)
			punish($k3wlt0k_weights[$digit] * $occurrences * 30,
				$occurrences . ' '
					. (($occurrences == 1) ? 'occurrence' : 'occurrences')
					. " of $digit");
	}

/*
This needs to be fixed so there aren't too many extraneous caps
	# An alpha caps is not lame in middle or at end, provided the first
	# alpha is caps.
	$capnick = $newnick;
	$newnick = preg_replace ("/^([^A-Za-z]*[A-Z].*[a-z].*?)[_-]?([A-Z])/e", "'\\1'.strtolower('\\2')", $newnick);
  
	# A caps first alpha is sometimes not lame
	$newnick = preg_replace ("/^([^A-Za-z]*)([A-Z])([a-z])/e", "'\\1'.strtolower('\\2').strtolower('\\3')", $newnick);
*/

	# Punish uppercase to lowercase shifts and vice-versa, modulo 
	# exceptions above
	$case_shifts = case_shifts($newnick);

	if ($case_shifts > 1 && preg_match("/[A-Z]/", $newnick))
		punish(slow_pow(9, $case_shifts),
			$case_shifts . ' case ' .
			(($case_shifts == 1) ? 'shift' : 'shifts'));

	# Punish lame endings (TorgoX, WraithX et al. might kill me for this :-)
	if(preg_match("/[XZ][^a-zA-Z]*$/", $newnick))
		punish(50, 'last alpha lame');

	# Punish letter to numeric shifts and vice-versa
	$number_shifts = number_shifts($newnick);

	if ($number_shifts > 1)
		punish(slow_pow(9, $number_shifts), 
			$number_shifts . ' letter/number ' .
			(($number_shifts == 1) ? 'shift' : 'shifts'));

	$newnick = preg_replace ("/[a-z]/", '', $newnick);

	# Punish extraneous caps
	$newnick = preg_split('//', $newnick);

	$caps = 0;
	for($i=0; $i<sizeof($newnick); $i++) {
		if(preg_match("/^([A-Z])$/", $newnick[$i]))
			$caps++;
	}

	if ($caps)
		punish(slow_pow(7, $caps), "$caps extraneous caps");

	# Now punish anything that's left
	$remains = preg_replace("/[A-Za-z0-9]/", '', $newnick);
	if(is_array($remains))
		$remains = implode('', $remains);
	$remains_length = strlen($remains);

	if ($remains)
		punish(50 * $remains_length + slow_pow(9, $remains_length),
			$remains_length . ' extraneous ' .
			(($remains_length == 1) ? 'symbol' : 'symbols'));

	printf ("<br/>Raw lameness score is %.2f<br/>", $score);

	$percentage = 100 * (1 + tanh(($score-400)/400)) * (1 - 1/(1+$score/5)) / 2;

	$digits = 2 * (2 - ceil(log(100 - $percentage) / log(10)));

	printf ("%.${digits}f is the percentage of suckage", $percentage);

}

function number_shifts ($shift) {

	$shift = preg_split('//', $shift);

	$shifts = 0;
	for($i=0; $i<sizeof($shift); $i++) {
		if(preg_match("/^([A-Za-z])$/", $shift[$i])) {
			$shifts += ($case == 'n' ? 1 : 0);
			$case = 'l';
		} elseif(preg_match("/^([0-9])$/", $shift[$i])) {
			$shifts += ($case == 'l' ? 1 : 0);
			$case = 'n';
		}
	}
	return $shifts - 1;
}

function case_shifts ($shift) {

	$shift = preg_replace("/\d/", '', $shift);
	$shift = preg_split('//', $shift);

	$shifts = 0;
	for($i=0; $i<sizeof($shift); $i++) {
		if(preg_match("/^([A-Z])$/", $shift[$i])) {
			$shifts += ($case == 'l' ? 1 : 0);
			$case = 'U';
		} elseif(preg_match("/^([a-z])$/", $shift[$i])) {
			$shifts += ($case == 'U' ? 1 : 0);
			$case = 'l';
		}
	}
	return $shifts - 1;
}

function slow_pow ($x, $y) {
  return pow($x, slow_exponent($y));
}

function slow_exponent ($x) {
  return 1.3 * $x * (1 - atan($x/6) *2/pi());
}

function punish ($damage, $reason) {
	global $score;

	$score += $damage;

	if($damage)
		printf ("%.2f lameness points awarded: $reason<br/>", $damage);
	else
		return;
}

switch($_GET['op']) {
	case "Check nick":
		nickometer($_GET["nick"]);
		break;
}

?>

<html>
	<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="get">
		<input type="text" name="nick" size="20">
		<input type="submit" name="op" value="Check nick">
	</form>
</html>
