<?php
 /*
     pSplit - class to draw spline splitted charts

     Version     : 2.1.3
     Made by     : Jean-Damien POGOLOTTI
     Last Update : 09/09/11

     This file can be distributed under the license you can find at :

                       http://www.pchart.net/license

     You can find the whole class documentation on the pChart web site.
 */

 define("TEXT_POS_TOP"		, 690001);
 define("TEXT_POS_RIGHT"	, 690002);

 /* pSplit class definition */
 class pSplit
  {
   var $pChartObject;
   var $pDataObject; // crmv@30014

   /* Class creator */
   // crmv@30014
   function __construct($Object = null, $pDataObject = null) {
   	$this->pDataObject = $pDataObject;
   }
   // crmv@30014e

   /* Create the encoded string */
   function drawSplitPath($Object,$Values,$Format="")
    {
     $this->pChartObject = $Object;

     $Spacing		= isset($Format["Spacing"]) ? $Format["Spacing"] : 20;
     $TextPadding	= isset($Format["TextPadding"]) ? $Format["TextPadding"] : 2;
     $TextPos		= isset($Format["TextPos"]) ? $Format["TextPos"] : TEXT_POS_TOP;
     $Surrounding       = isset($Format["Surrounding"]) ? $Format["Surrounding"] : NULL;
     $Force		= isset($Format["Force"]) ? $Format["Force"] : 70;
     $Segments		= isset($Format["Segments"]) ? $Format["Segments"] : 15;
     // crmv@30014
     $CyclePalette  = isset($Format["CyclePalette"]) ? $Format["CyclePalette"] : FALSE;
     // crmv@30014e
     $FontSize		= $Object->FontSize;
     $X1		= $Object->GraphAreaX1;
     $Y1		= $Object->GraphAreaY1;
     $X2		= $Object->GraphAreaX2;
     $Y2		= $Object->GraphAreaY2;

     /* Data Processing */
     $Data    = $Values->getData();
     $Palette = $Values->getPalette();
     $PaletteOrigSize = count($Palette); // crmv@30014

     $LabelSerie = $Data["Abscissa"];
     $DataSerie  = "";

     foreach($Data["Series"] as $SerieName => $Value)
      { if ( $SerieName != $LabelSerie && $DataSerie == "" ) { $DataSerie = $SerieName; } }

     $DataSerieSum   = array_sum($Data["Series"][$DataSerie]["Data"]);
     $DataSerieCount = count($Data["Series"][$DataSerie]["Data"]);

     /* Scale Processing */
     if ( $TextPos == TEXT_POS_RIGHT )
      $YScale     = (($Y2-$Y1) - (($DataSerieCount+1)*$Spacing)) / $DataSerieSum;
     else
      $YScale     = (($Y2-$Y1) - ($DataSerieCount*$Spacing)) / $DataSerieSum;
     $LeftHeight = $DataSerieSum * $YScale;

     /* Re-compute graph width depending of the text mode choosen */
     if ( $TextPos == TEXT_POS_RIGHT )
      {
       $MaxWidth = 0;
       foreach($Data["Series"][$LabelSerie]["Data"] as $Key => $Label)
        {
         $Boundardies = $Object->getTextBox(0,0,$Object->FontName,$Object->FontSize,0,$Label);
         if ( $Boundardies[1]["X"] > $MaxWidth ) { $MaxWidth = $Boundardies[1]["X"] + $TextPadding*2; }
        }
       $X2 = $X2 - $MaxWidth;
      }

     /* Drawing */
     $LeftY    = ((($Y2-$Y1) / 2) + $Y1) - ($LeftHeight/2);
     $RightY   = $Y1;
     $VectorX  = (($X2-$X1) / 2);
     $ID = 0; // crmv@30014

     foreach($Data["Series"][$DataSerie]["Data"] as $Key => $Value)
      {
       if ( isset($Data["Series"][$LabelSerie]["Data"][$Key]) )
        $Label = $Data["Series"][$LabelSerie]["Data"][$Key];
       else
        $Label = "-";

       $LeftY1 = $LeftY;
       $LeftY2 = $LeftY + $Value * $YScale;

       $RightY1 = $RightY + $Spacing;
       $RightY2 = $RightY + $Spacing + $Value * $YScale;;

       // crmv@30014
       if ( !isset($Palette[$ID]["R"]) ) {
       	if ($CyclePalette && $PaletteOrigSize > 0)
       		$Color = $Palette[$ID % $PaletteOrigSize];
       	else
       		$Color = $this->pChartObject->getRandomColor();
       	$Palette[$ID] = $Color;
       	if ($this->pDataObject) $this->pDataObject->savePalette($ID,$Color);
       }
       // crmv@30014e

       $Settings = array("R"=>$Palette[$Key]["R"],"G"=>$Palette[$Key]["G"],"B"=>$Palette[$Key]["B"],"Alpha"=>$Palette[$Key]["Alpha"],"NoDraw"=>TRUE,"Segments"=>$Segments,"Surrounding"=>$Surrounding);

       $PolyGon = "";

       $Angle    = $Object->getAngle($X2,$RightY1,$X1,$LeftY1);
       $VectorX1 = cos(deg2rad($Angle+90)) * $Force + ($X2-$X1)/2 + $X1;
       $VectorY1 = sin(deg2rad($Angle+90)) * $Force + ($RightY1-$LeftY1)/2 + $LeftY1;
       $VectorX2 = cos(deg2rad($Angle-90)) * $Force + ($X2-$X1)/2 + $X1;
       $VectorY2 = sin(deg2rad($Angle-90)) * $Force + ($RightY1-$LeftY1)/2 + $LeftY1;

       $Points = $Object->drawBezier($X1,$LeftY1,$X2,$RightY1,$VectorX1,$VectorY1,$VectorX2,$VectorY2,$Settings);
       foreach($Points as $Key => $Pos) { $PolyGon[] = $Pos["X"]; $PolyGon[] = $Pos["Y"]; }


       $Angle    = $Object->getAngle($X2,$RightY2,$X1,$LeftY2);
       $VectorX1 = cos(deg2rad($Angle+90)) * $Force + ($X2-$X1)/2 +$X1;
       $VectorY1 = sin(deg2rad($Angle+90)) * $Force + ($RightY2-$LeftY2)/2 + $LeftY2;
       $VectorX2 = cos(deg2rad($Angle-90)) * $Force + ($X2-$X1)/2 +$X1;
       $VectorY2 = sin(deg2rad($Angle-90)) * $Force + ($RightY2-$LeftY2)/2 + $LeftY2;

       $Points = $Object->drawBezier($X1,$LeftY2,$X2,$RightY2,$VectorX1,$VectorY1,$VectorX2,$VectorY2,$Settings);
       $Points = array_reverse($Points);
       foreach($Points as $Key => $Pos) { $PolyGon[] = $Pos["X"]; $PolyGon[] = $Pos["Y"]; }

       $Object->drawPolygon($PolyGon,$Settings);

       if ( $TextPos == TEXT_POS_RIGHT )
        $Object->drawText($X2+$TextPadding,($RightY2-$RightY1)/2+$RightY1,$Label,array("Align"=>TEXT_ALIGN_MIDDLELEFT));
       else
        $Object->drawText($X2,$RightY1-$TextPadding,$Label,array("Align"=>TEXT_ALIGN_BOTTOMRIGHT));

       $LeftY  = $LeftY2;
       $RightY = $RightY2;
       ++$ID; // crmv@30014
      }
    }
  }
?>
