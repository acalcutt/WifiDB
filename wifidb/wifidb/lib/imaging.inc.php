<?php
/*
Database.inc.php, holds the database interactive functions.
Copyright (C) 2011 Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

ou should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
*/

class imaging {
    function  __construct()
    {
        $this->width = 1024;
        $this->height = 768;
        $this->grid_spacing = 100;
        $this->line_thickness = 4;
        $this->canvas = NULL;
        $this->barcanvas = NULL;
        $this->linecanvas = NULL;
        #
        $this->data = array();
        $this->label_x = array();
        $this->label_y = array();
        #
        $this->write_file = 0;
        $this->draw_grid = 1;
        $this->dynamic_canvas = 1;
        #
        $this->color_gray = "128,128,128";
        $this->color_white = "255,255,255";
        $this->color_black = "000,000,000";
        $this->color_red = "255,000,000";
        $this->color_green = "000,255,000";
        $this->color_blue = "000,000,255";
        #
    }
    #
    function set_canvas_size($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
    }
    #
    function addbar($data, $color_bar)
    {
        $count = count($data);
        if($this->dynamic_canvas)
        {
            if($count > 100)
            {
                $this->width = $count/4;
                echo "Height: ".$this->height."\r\n";
            }else
            {
                $this->width = $count*6.2;
            }
            #
            $max = max($data);
            if($max > 100)
            {
                $this->height = $max/4;
                echo "Height: ".$this->height."\r\n";
            }else
            {
                $this->height = $max*6.2;
            }
            #
            echo "Width: ".$this->width."\r\n";
            echo "Height: ".$this->height."\r\n";
        }
        #
        $barcanvas = imagecreatetruecolor ($this->width, $this->height);
        imagesetthickness($barcanvas, $this->line_thickness);
        foreach($data as $key=>$d)
        {
            imageline($barcanvas, $y ,459-($d*4), $y=$y+6 ,459-($data[$key+1]*4) ,imagecolorallocate($this->canvas, $color_bar));
        }
        #
    }
    #
    function pop() //generate PNG image and write file, if wanted
    {
        $this->canvas = imagecreatetruecolor ($this->width, $this->height);
        imagefilledrectangle($this->canvas, 0, 0, $this->width, $this->height, imagecolorallocate($this->canvas, $this->color_white));
        if($this->draw_grid)
        {
            $this->imagegrid();
        }



    }
    #
    function imagegrid()
    {
        $ws = $this->width/$this->grid_spacing;
        $hs = $this->height/$this->grid_spacing;

        for($iw=0; $iw < $ws; ++$iw)
        {
            imageline($this->canvas, ($iw-0)*$this->grid_spacing, 60 , ($iw-0)*$this->grid_spacing, $w , imagecolorallocate($this->canvas, $this->color_gray));
        }
        for($ih=0; $ih<$hs; ++$ih)
        {
            imageline($this->canvas, 0, $ih*$s, $w , $ih*$s, imagecolorallocate($this->canvas, $this->color_gray));
        }
    }
    #
}
?>