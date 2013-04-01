<?php
/*
   Author:     Alberto Gil Tesa
   WebSite:    http://giltesa.com
   License:    CC BY-NC-SA 3.0
               http://goo.gl/CTYnN

   Project:    WordPress FilmAffinity Widget
   File:       \includes\Film.php
   Date:       28/03/2013
*/
?>
<?php

    class Film
    {
        private $title;
        private $synopsis;
        private $note;
        private $imageSmall;
        private $imageMedium;
        private $link;



        /**
         * Movie class costructor.
         * Costructor de la clase Movie.
         *
         * @param string $title
         * @param string $synopsis
         * @param string $note
         * @param string $link
         * @param array  $dataImage
         */
        public function __construct( $title, $synopsis, $note, $link, $dataImage )
        {
            // Are replaced or eliminated all non-ASCII characters:
            $this->title         = preg_replace( '/[^(\x20-\x7F)]*/', '', htmlentities($title) );
            $this->synopsis      = preg_replace( '/[^(\x20-\x7F)]*/', '', htmlentities($synopsis) );

            $this->note          = $note;
            $this->link          = $link;

            $this->imageSmall    = $this->downloadImage( $dataImage['urlImage']                                    , $dataImage['idWidget'], $dataImage['idImage'], "s" );
            $this->imageMedium   = $this->downloadImage( str_replace( "small" , "full"  ,  $dataImage['urlImage'] ), $dataImage['idWidget'], $dataImage['idImage'], "m" );
        }



        /**
         * Movie class destroyer.
         * Destructor de la clase Movie.
         */
        function __destruct()
        {

        }



        /**
         * Returns the name of the movie.
         * Devuelve el nombre de la pelicula.
         *
         * @return string
         */
        public function getTitle()
        {
            return $this->title;
        }



        /**
         * Returns the synopsis of the movie.
         * Devuelve la synopsis de la pelicula.
         *
         * @return string
         */
        public function getSynopsis()
        {
            return $this->synopsis;
        }



        /**
         * Returns the note that has the movie.
         * Devuelve la note que tiene la pelicula.
         *
         * @return string
         */
        public function getNote()
        {
            return $this->note;
        }



        /**
         * Returns the link to the small image of the movie.
         * Devuelve el link hacia la imagen pequeña de la pelicula.
         *
         * @return string
         */
        public function getImageSmall()
        {
            return $this->imageSmall;
        }



        /**
         * Returns the link to the medium image of the movie.
         * Devuelve el link hacia la imagen mediana de la pelicula.
         *
         * @return string
         */
        public function getImageMedium()
        {
            return $this->imageMedium;
        }



        /**
         * Returns the link to the movie.
         * Devuelve el link hacia la pelicula.
         *
         * @return string
         */
        public function getLink()
        {
            return $this->link;
        }



        /**
         * Download the image and save it in the temporary directory. Then returns the URL to the image.
         * Descarga la imagen y la guarda en el directorio temporal. Despues devuelve la URL a la imagen.
         *
         * @param  String  $urlImage
         * @param  String  $idWidget
         * @param  String  $idImage
         * @param  String  $sizeImage
         * @return String
         */
        private function downloadImage( $urlImage, $idWidget, $idImage, $sizeImage )
        {
            $fileName = $idWidget . $idImage . $sizeImage . '.jpg';

            copy( $urlImage, PLUGIN_LOCAL_PATH .'temp/'. $fileName );

            return PLUGIN_URL_PATH .'temp/'. $fileName;
        }



        /**
         * Returns all the information of the movie.
         * Devuelve toda la informacion de la pelicula.
         *
         * @return string
         */
        public function toString()
        {
            return("MOVIE=[<blockquote>"
                    . "<b>Title:</b> "    . $this->title       . "<br/>"
                    . "<b>Synopsis:</b> " . $this->synopsis    . "<br/>"
                    . "<b>Note:</b> "     . $this->note        . "<br/>"
                    . "<b>ImageS:</b> "   . $this->imageSmall  . "<br/>"
                    . "<b>ImageM:</b> "   . $this->imageMedium . "<br/>"
                    . "<b>Link:</b> "     . $this->link        . "<br/>"
                    . "</blockquote>]<br/>");
        }



    } // End Film class.

?>