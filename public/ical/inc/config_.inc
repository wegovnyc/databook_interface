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

    /**
     *  Configuration générale
     */
    $title = "Agenda";
    $logo = "calendar.png";
	
    /** 
     *  Configuration LDAP
     */
    // Accès serveur
    $serverldap = "10.0.0.1";
    $portldap = "389";
    $rootdn = "ou=manager_ldap,dc=domain,dc=local";
    $rootpw = "manager_password";
    // Recherche utilisateurs
    $dn = "ou=Users,dc=domain,dc=local";
    $filtre="(&(sn=*)(mail=*))";
    $restriction = array( "sn", "givenname", "mail" );
   
    /**
     *  Configuration serveur mail (iCal ou CalDAV)
     */
    #$cal_type = "caldav"; // "caldav" or "ical"
    $cal_type = "ical"; // "caldav" or "ical"
    $cal_https = true;
    $cal_server = "mail.myserverzimbra.fr";
    $cal_user = "calreader@mydomain.com";
    $cal_pass = "user_password";       

?>
