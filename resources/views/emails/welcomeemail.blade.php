<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>ABO MIO</title>
    <style type="text/css">
        body {
            font-family: Source Sans Pro, Helvetica, Arial, sans-serif;
            background-color: #ffffff;
        }

        .b {
            font-weight: bold;
        }

        .text {
            font-family: Source Sans Pro, Helvetica, Arial, sans-serif;
            font-size: 16px;
            line-height: 20px;
            color: #0C56A6;
            padding-bottom: 15px;
            padding-left: 10px;
            padding-right: 10px;
        }

        .h1 {
            font-size: 16px;
            line-height: 45px;
            font-weight: 700;
            color: #0C56A6;
            margin-top: 0;
            margin-bottom: 0;
        }

        .tb-claims-data {
            background-color: #F7C3C9;
            width: 100%;
            padding-bottom: 15px;
            padding-top: 15px;
            padding-right: 10px;
            color: #0C56A6;
            margin-top: 15px;
            margin-bottom: 15px;
        }

        .tb-claims-data td {
            padding-bottom: 0;
            padding-top: 0;
        }

        .footer {
            background-color: #F7C3C9;
            width: 100%;
            color: #F7C3C9;
            text-align: center;
        }

        .links {
            padding: 10px 0 10px 0;
            color: #F7C3C9;
        }

        a, a:visited {
            text-decoration: none;
            color: #0C56A6;
            font-size: 8pt;
        }

        .p-b-img {
            padding-bottom: 20px;
        }

        .small-text {
            font-size: 8pt;
            color: #0C56A6;
        }
    </style>
</head>
<body>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td width="100%" bgcolor="#fafafa">
            <table width="100%"
                   align="center"
                   cellpadding="0"
                   cellspacing="0"
                   border="0"
                   style="max-width:650px; background-color: #F7C3C9;">
                <tr>
                    <td width="100%" class="b-left" align="center">
                        <img src="https://apptiq.ch/app/images/logos/dio_mio_logo.png"
                             width="auto"
                             height="80"
                             style="border:thin; display:block"
                             class="p-b-img">
                    </td>
                </tr>
                <tr>
                    <td height="40">&nbsp;</td>
                </tr>
                <tr>
                    <td align="center">
                        <img src="https://apptiq.ch/app/images/logos/DIOMIO_Ciao.png"
                             alt="ciao"
                             class="p-b-img"
                             width="100">
                    </td>
                </tr>
                <tr>
                    <td height="40">&nbsp;</td>
                </tr>
                <tr>
                    <td class="text" style="text-align: center;">
                        Grazie mille für deine Anmeldung! Wir freuen uns, dir ab sofort monatlich deine Pizza-Lieblinge
                        zustellen zu dürfen. Wir melden uns von nun an jeden Monat, bevor die Pizzas bei dir ankommen,
                        mit einer Liefer-Erinnerung. So stellen wir sicher, dass du deine regelmässige, köstliche
                        DIO/MIO-Dosis nicht verpasst...
                    </td>
                </tr>
                <tr>
                    <td height="40">&nbsp;</td>
                </tr>
                <tr>
                    <td class="tb-claims-data" align="center">
                        <table>
                            <tr>
                                <td colspan="2" class="text">
                                    <h3><b>HIER DEINE BESTELLDETAILS</b></h3>
                                </td>
                            </tr>
                            <tr>
                                <td class="text"><b>ANZAHL PIZZAS</b></td>
                                <td class="text">{{$boxSize}}</td>
                            </tr>
                            <tr>
                                <td class="text"><b>PIZZAS</b></td>
                                <td class="text">{{$pizzas}}</td>
                            </tr>
                            <tr>
                                <td class="text"><b>LIEFERTERMIN</b></td>
                                <td class="text">{{$interval}}</td>
                            </tr>
                            <tr>
                                <td class="text"><b>PREIS</b></td>
                                <td class="text">{{$price}} CHF (inkl. Lieferung)</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td height="40">&nbsp;</td>
                </tr>
                <tr>
                    <td class="text" style="text-align: center;">
                        <img src="https://apptiq.ch/app/images/logos/DIOMIO_chevuoi.png"
                             alt="hand"
                             width="80"
                             class="p-b-img">
                        <br/>Bei Fragen einfach fragen!!!
                        <br/>PS: Auch wenn unser E-Mail-Programm zunehmend menschliche Züge trägt – das ist trotzdem
                        eine automatisch generierte Mail, auf die du vernünftiger Weise nicht antworten solltest.<br/>
                    </td>
                </tr>
                <tr>
                    <td height="40">&nbsp;</td>
                </tr>
                <tr class="footer">
                    <td>
                        <table class="links" align="center">
                            <tr>
                                <td class="small-text">
                                    DIO/MIO Basel AG | Theaterstrasse 10 | 4051 Basel | +41 61 273 06 56 |
                                    <a href="https://diomio.ch">diomio.ch</a> | <a href="mailto:abo@diomio.ch">abo@diomio.ch</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
