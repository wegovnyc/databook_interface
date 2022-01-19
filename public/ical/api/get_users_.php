<?php
/*
webagenda-viewer (calendar viewer - ical & dav)
 
Copyright (C) 2017  Noël Martinon

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
?>

<?php

	require_once('../inc/config.inc');
	require_once('../inc/common.inc');
		
	try {
		$ds=ldap_connect($serverldap);

		$r=@ldap_bind($ds,$rootdn,$rootpw);		

		$sr=ldap_search($ds, $dn, $filtre, $restriction);
		$info = ldap_get_entries($ds, $sr);
							
		for ($i=0; $i<$info["count"]; $i++)
		{
			$nom[$i]=$info[$i]["sn"][0]." ".$info[$i]["givenname"][0];
			if (isset($info[$i]["mail"]))
				{$mail[$nom[$i]]= $info[$i]["mail"][0] ;}
			else
				{$mail[$nom[$i]]= '' ;}
		}

		usort($nom, 'wd_unaccent_compare_ci');

		for ($i=0; $i<$info["count"]; $i++)
		{
			if ($mail[$nom[$i]]!='')
				$entry[$nom[$i]] = $mail[$nom[$i]];
		}
		
		header('Content-type: aplication/json');
		echo json_encode ($entry);
	}
	catch (Exception $e) {
		
		http_response_code(500);
	}
?>
