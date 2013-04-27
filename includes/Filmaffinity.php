<?php
/*
   Author:     Alberto Gil Tesa
   WebSite:    http://giltesa.com
   License:    CC BY-NC-SA 3.0
               http://goo.gl/CTYnN

   Project:    WordPress FilmAffinity Widget
   File:       \includes\Filmaffinity.php
   Date:       27/04/2013
*/
?>
<?php

    class Filmaffinity
    {
        private $page;
        private $inEnglish;



        /**
         * Class constructor that gets profile page of the user ID.
         * Constructor de la clase FilmAffinity que obtiene la pagina del perfil de usuario del ID indicado.
         *
         * @param String $userID
         * @param String $language
         */
        public function __construct( $userID , $language )
        {
            if( !strcmp($language ,"en") )
            {
                $url = "http://www.filmaffinity.com/en/userratings.php?user_id=$userID";
                $this->inEnglish = true;
            }
            else
            {
                $url = "http://www.filmaffinity.com/es/userratings.php?user_id=$userID";
                $this->inEnglish = false;
            }
            $this->page = file_get_html( $url );
        }



        /**
         * Filmaffinity class destroyer.
         * Destructor de la clase Filmaffinity.
         */
        function __destruct()
        {
            unset($this->page);
        }



        /**
         * Returns a String with the nick of the user.
         * Devuelve un String con el nick del usuario.
         *
         * @return
         */
        public function getUser()
        {
            if( $this->page && $this->inEnglish )
            {
                return $this->page->getElementById('span[id=nick] > b')->innertext;
            }
            else if( $this->page )
            {
                return $this->page->getElementById('span[id=nick]')->innertext;
            }
        }



        /**
         * Returns the average film voted by the user.
         * Devuelve la media de las películas votadas por el usuario.
         *
         * @return
         */
        public function getAvgVotes()
        {
            if( $this->page && $this->inEnglish )
            {
                return $this->page->find('#uprofile > tbody > tr > td > span', 3)->innertext;
            }
            else if( $this->page )
            {
                return $this->page->getElementById('#avg_votes')->innertext;
            }
        }



        /**
         * Returns the total number of films voted.
         * Devuelve el numero total de peliculas votadas.
         *
         * @return
         */
        public function getMoviesRated()
        {
            if( $this->page && $this->inEnglish )
            {
                return $this->page->find('#uprofile > tbody > tr > td > table > tbody > tr > td > b', 1)->innertext;
            }
            else if( $this->page )
            {
                return $this->page->find('#uprofile > div.user_data > div#stats > dl > dt', 1)->innertext;
            }
        }



        /**
         * Returns an array of films.
         * Devuelve un array de Películas.
         *
         * @param  Integer $numFilms
         * @param  Boolean $isGetDescription
         * @param  String  $idWidget
         * @return array   Film
         */
        public function getFilms( $numFilms, $isGetDescription=false, $idWidget )
        {
            if( $this->page && $this->inEnglish )
            {
                return $this->getFilmsEN($numFilms, $isGetDescription, $idWidget);
            }
            else if( $this->page )
            {
                return $this->getFilmsES($numFilms, $isGetDescription, $idWidget);
            }
        }



        /**
         * Returns an array of films in spanish.
         * Devuelve un array de Películas en español.
         *
         * @param   Integer $numFilms
         * @param   Integer $isGetDescription
         * @param   String  $idWidget
         * @return  array   Film
         */
        private function getFilmsES( $numFilms, $isGetDescription, $idWidget )
        {
            if( $this->page )
            {
                $title       = "";
                $synopsis    = "";
                $note        = "";
                $link        = "";
                $imageSmall  = "";
                $imageMedium = "";



                $result = $this->page->find("div[id=amovies_cont] > table.amovie_info");

                foreach( $result as $countFilm => $res )
                {
                    // Title:
                    $title = $res->find("div.mc-title > a", 0)->plaintext;

                    // Note:
                    $note = $res->find("tbody > tr > td > div > div", 6)->plaintext;

                    // Link:
                    $link = "http://www.filmaffinity.com" . $res->find('div.mc-title > a', 0)->href;

                    // Film Small Image:
                    $imageSmall = $res->find("img", 0)->src;


                    // More info:
                    if( $isGetDescription )
                    {
                        $result2 = file_get_html($link);

                        // Synopsis:
                        $dt = $result2->find("#left-column > dl.movie-info > dt");//->plaintext;
                        $dd = $result2->find("#left-column > dl.movie-info > dd");//->plaintext;
                        
                        foreach( $dt as $i=>$text )
                        {
                            if( !strcmp($text->plaintext, "Sinopsis") )
                            {
                                $synopsis = $dd[$i]->plaintext;
                                break;
                            }
                        }

                        // Film Medium Image:
                        $imageMedium = $result2->find("#movie-main-image-container", 0)->find("img", 0)->src;
                    }


                    // It creates a new movie with the data read:
                    $films[$countFilm] = new Film( $title, $synopsis, $note, $link, $imageSmall, $imageMedium, $idWidget, $countFilm+1 );
                    $countFilm = $countFilm + 1;

                    if( $countFilm == $numFilms || $countFilm == 22 )
                        return $films;

                }
            }
        }



        /**
         * Returns an array of films in english.
         * Devuelve un array de Películas en ingles.
         *
         * @param  Integer $numFilms
         * @param  Integer $isGetDescription
         * @param  String  $idWidget
         * @return array   Film
         */
        private function getFilmsEN( $numFilms, $isGetDescription, $idWidget )
        {
            if( $this->page )
            {
                $title       = "";
                $synopsis    = "";
                $note        = "";
                $link        = "";
                $imageSmall  = "";
                $imageMedium = "";


                // All notes are obtained:
                $result = $this->page->find('span.wrat');

                $countNote = 0;
                foreach( $result as $res )
                {
                    $temp = str_replace(' ', '', $res->innertext);

                    if( is_numeric($temp) )
                    {
                        $allNotes[$countNote] = $temp;
                        $countNote = $countNote + 1;
                    }
                }


                $result = $this->page->find("html > body > table.ot > tbody > tr > td > table > tbody");

                foreach( $result as $countFilm => $res )
                {
                    // Title:
                    $title = $this->page->find("a.ntext", $countFilm)->plaintext;

                    // Note:
                    $note = $allNotes[$countFilm];
                    
                    // Link:
                    $link = "http://www.filmaffinity.com" . $this->page->find('a.ntext', $countFilm)->href;                    

                    // Film Image:
                    $image = $this->page->find("div.movie-card-7 > table > tbody > tr > td > a > img", $countFilm)->src;

                    // Synopsis:
                    if( $isGetDescription )
                    {
                        $result2 = file_get_html($link)->find("html > body > table.ot > tbody > tr > td > table > tbody > tr > td > table > tbody > tr > td > table > tbody > tr");

                        foreach( $result2 as $res2 )
                        {
                            $th = "";
                            $td = "";

                            if( isset($res2->children(0)->children(0)->plaintext) )
                                $th = $res2->children(0)->children(0)->plaintext;

                            if( isset($res2->children(1)->plaintext) )
                                $td = $res2->children(1)->plaintext;

                            if( !strcmp($th ,"SYNOPSIS/PLOT") )
                            {
                                $synopsis = $td;
                                break;
                            }
                        }
                    }


                    // It creates a new movie with the data read:
                    $films[$countFilm] = new Film( $title, $synopsis, $note, $link, $imageSmall, str_replace("small","full",$imageSmall), $idWidget, $countFilm+1 );
                    $countFilm = $countFilm + 1;

                    if( $countFilm == $numFilms || $countFilm == 22 )
                        return $films;
                }
            }
        }


    } // End Filmaffinity class.

?>