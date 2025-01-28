<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8"> <!-- utf-8 works for most cases -->
    <meta name="viewport" content="width=device-width"> <!-- Forcing initial-scale shouldn't be necessary -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge"> <!-- Use the latest (edge) version of IE rendering engine -->
  <title>Sports for Charity</title> <!-- The title tag shows in email notifications, like Android 4.4. -->

  <!-- CSS Reset -->
    <style type="text/css">

      /* What it does: Remove spaces around the email design added by some email clients. */
      /* Beware: It can remove the padding / margin and add a background color to the compose a reply window. */
        html,
        body {
            margin: 0 !important;
            padding: 0 !important;
            height: 100% !important;
            width: 100% !important;
        }

        /* What it does: Stops email clients resizing small text. */
        * {
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
            -webkit-font-smoothing: antialiased;
        }

        /* What it does: Forces Outlook.com to display emails full width. */
        .ExternalClass {
            width: 100%;
        }

        /* What is does: Centers email on Android 4.4 */
        div[style*="margin: 16px 0"] {
            margin:0 !important;
        }

        /* What it does: Stops Outlook from adding extra spacing to tables. */
        table,
        td {
            mso-table-lspace: 0pt !important;
            mso-table-rspace: 0pt !important;
        }

        /* What it does: Fixes webkit padding issue. Fix for Yahoo mail table alignment bug. Applies table-layout to the first 2 tables then removes for anything nested deeper. */
        table {
            border-spacing: 0 !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            margin: 0 auto !important;
        }
        table table table {
            table-layout: auto;
        }

        table.striped {
            width: auto;
            min-width: 100%;
        }

        table.striped tbody tr:nth-of-type(odd) { 
            background-color: #f9f9f9; 
        }

        table.striped tbody tr {
            border-top: 1px solid #eee;
        }

        table.striped tr td, table.striped tr th {
            padding: 10px;
            white-space: nowrap;
            text-align: left;
        }

        /* What it does: Uses a better rendering method when resizing images in IE. */
        img {
            -ms-interpolation-mode:bicubic;
        }

        /* What it does: Overrides styles added when Yahoo's auto-senses a link. */
        .yshortcuts a {
            border-bottom: none !important;
        }

        /* What it does: Another work-around for iOS meddling in triggered links. */
        a[x-apple-data-detectors] {
            color:inherit !important;
        }

        a.btn {
            background: {{ isset($virtual) && $virtual ? '#0e75bc' : (isset($rankings) && $rankings ? '#397abb' : '#000000') }};
            border-color: {{ isset($virtual) && $virtual ? '#0e75bc' : (isset($rankings) && $rankings ? '#397abb' : '#000000') }};
            color: white;
            font-size: 12px;
            border-radius: 3px;
            padding: 5px 10px;
            border-width: 2px;
            font-weight: 400;
            transition: all 100ms ease-in;
            line-height: 1.5;
            display: inline-block;
            margin-bottom: 0;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            touch-action: manipulation;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
        }

        a.btn:hover, a.btn:focus {
            border-color: #ffffff !important;
        }

        span.label {
            position: relative;
            border-radius: 2px;
            font-size: 12px;
            padding: .3em .6em .3em;
            display: inline;
            font-weight: 700;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
        }
    </style>

    <!-- Progressive Enhancements -->
    <style>

        /* What it does: Hover styles for buttons */
        .button-td,
        .button-a {
            transition: all 100ms ease-in;
        }
        .button-td:hover,
        .button-a:hover {
            background: #555555 !important;
            border-color: #555555 !important;
        }

        /* Media Queries */
        @media screen and (max-width: 480px) {

            /* What it does: Forces elements to resize to the full width of their container. Useful for resizing images beyond their max-width. */
            .fluid,
            .fluid-centered {
                width: 100% !important;
                max-width: 100% !important;
                height: auto !important;
                margin-left: auto !important;
                margin-right: auto !important;
            }
            /* And center justify these ones. */
            .fluid-centered {
                margin-left: auto !important;
                margin-right: auto !important;
            }

            /* What it does: Forces table cells into full-width rows. */
            .stack-column,
            .stack-column-center {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                direction: ltr !important;
            }
            /* And center justify these ones. */
            .stack-column-center {
                text-align: center !important;
            }

            /* What it does: Generic utility class for centering. Useful for images, buttons, and nested tables. */
            .center-on-narrow {
                text-align: center !important;
                display: block !important;
                margin-left: auto !important;
                margin-right: auto !important;
                float: none !important;
            }
            table.center-on-narrow {
                display: inline-block !important;
            }

        }

    </style>
    
    @yield('email_css')

</head>
<body width="100%" bgcolor="{{ isset($virtual) && $virtual ? '#0e75bc' : (isset($rankings) && $rankings ? '#397abb' : '#000000') }}" style="margin: 0;">
<table cellpadding="0" cellspacing="0" border="0" height="100%" width="100%" bgcolor="{{ isset($virtual) && $virtual ? '#0e75bc' : (isset($rankings) && $rankings ? '#397abb' : '#000000') }}" style="border-collapse:collapse;"><tr><td>
    <center style="width: 100%;">

        <!-- Visually Hidden Preheader Text : BEGIN -->
        <div style="display:none;font-size:1px;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;mso-hide:all;font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
            {{ isset($virtual) && $virtual ? 'Virtual Marathon Series' : (isset($rankings) && $rankings ? 'RunThroughHub' : 'Sports for Charity') }}
        </div>
        <!-- Visually Hidden Preheader Text : END -->

        @yield('hero')

        <div style="max-width: 660px;">
            <!--[if (gte mso 9)|(IE)]>
            <table cellspacing="0" cellpadding="0" border="0" width="660" align="center">
            <tr>
            <td>
            <![endif]-->

            <!-- Email Header : BEGIN -->

                @yield('header')

            <!-- Email Header : END -->

            <!-- Email Body : BEGIN -->
            <table cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="max-width: 660px;">
                <tr>
                    <td>
                        <table bgcolor="#ffffff" cellspacing="0" cellpadding="0" border="0" width="100%" style="-moz-border-radius: {{ isset($isPremium) && $isPremium ? '0 0 3px 3px' : '3px' }}; -webkit-border-radius: {{ isset($isPremium) && $isPremium ? '0 0 3px 3px' : '3px' }}; border-radius: {{ isset($isPremium) && $isPremium ? '0 0 3px 3px' : '3px' }};">
                          <tr>
                            <td style="margin-top: 30px; padding: 40px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 16px; mso-height-rule: exactly; line-height: 1.5em; color: #616161;">

                                @yield('content')

                                @if(isset($unsubscribe) && $unsubscribe)
                                    <p style="margin-top: 64px; font-size: 10px; color: #888; text-align: center;">No longer want to receive emails like this? <a href="{{ url('/emails/preferences') }}">Unsubscribe or manage your preferences here.</a></p>
                                @endif

                            </td>
                          </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!-- Email Body : END -->

            <!-- Email Footer : BEGIN -->
            <table cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="max-width: 660px;">

                <!-- Three Even Columns : BEGIN -->
                {{--@if(!(isset($virtual) && $virtual) || !(isset($rankings) && $rankings))--}}
                @if((!isset($isPremium) || !$isPremium) && (!isset($virtual) || (isset($virtual) && !$virtual)) && (!isset($rankings) || (isset($rankings) && !$rankings)))
                    <tr>
                        <td align="center" height="100%" valign="top" width="100%" style="padding: 40px 0 0 0;">
                            <!--[if mso]>
                            <table border="0" cellspacing="0" cellpadding="0" align="center" width="660">
                            <tr>
                            <td align="center" valign="top" width="660">
                            <![endif]-->
                            <table border="0" cellpadding="0" cellspacing="0" align="center" width="100%" style="max-width:660px;">
                                <tr>
                                    <td align="center" valign="top" style="font-size:0;">
                                        <!--[if mso]>
                                        <table border="0" cellspacing="0" cellpadding="0" align="center" width="660">
                                        <tr>
                                        <td align="left" valign="top" width="220">
                                        <![endif]-->
                                        <div style="display:inline-block; margin: 0 -2px; max-width:33.33%; min-width:220px; vertical-align:top; width:100%;" class="stack-column">
                                            <table cellspacing="0" cellpadding="0" border="0" width="100%">
                                                <tr>
                                                    <td style="padding: 10px 10px;">
                                                        <table cellspacing="0" cellpadding="0" border="0" width="100%" style="font-size: 14px;text-align: left;">
                                                            <tr>
                                                                <td>
                                                                    <a href="http://runforcharity.com/">
                                                                        <img src="{{ (isset($virtual) && $virtual) || (isset($rankings) && $rankings) ? url('/img/brand/logo-run.svg') : url('images/emails/run-for-charity.jpg') }}" width="126" alt="Run for Chairty" style="border: 0;margin: 0 auto; display: block;max-width: 200px;height: auto;" class="center-on-narrow">
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <!--[if mso]>
                                        </td>
                                        <td align="left" valign="top" width="220">
                                        <![endif]-->
                                        <div style="display:inline-block; margin: 0 -2px; max-width:33.33%; min-width:220px; vertical-align:top; width:100%;" class="stack-column">
                                            <table cellspacing="0" cellpadding="0" border="0" width="100%">
                                                <tr>
                                                    <td style="padding: 10px 10px;">
                                                        <table cellspacing="0" cellpadding="0" border="0" width="100%" style="font-size: 14px;text-align: left;">
                                                            <tr>
                                                                <td>
                                                                    <a href="http://www.sportforcharity.com/">
                                                                        <img src="{{ (isset($virtual) && $virtual) || (isset($rankings) && $rankings) ? url('/img/brand/logo-sport.svg') : url('images/emails/sport-for-charity.jpg') }}" width="115" alt="Sport for Charity" style="border: 0;margin: 0 auto; display: block;max-width: 200px;height: auto;" class="center-on-narrow">
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <!--[if mso]>
                                        </td>
                                        <td align="left" valign="top" width="220">
                                        <![endif]-->
                                        <div style="display:inline-block; margin: 0 -2px; max-width:33.33%; min-width:220px; vertical-align:top; width:100%;" class="stack-column">
                                            <table cellspacing="0" cellpadding="0" border="0" width="100%">
                                                <tr>
                                                    <td style="padding: 10px 10px;">
                                                        <table cellspacing="0" cellpadding="0" border="0" width="100%" style="font-size: 14px;text-align: left;">
                                                            <tr>
                                                                <td>
                                                                    <a href="http://cycleforcharity.com/">
                                                                        <img src="{{ (isset($virtual) && $virtual) || (isset($rankings) && $rankings) ? url('/img/brand/logo-cycle.svg') : url('images/emails/cycle-for-charity.jpg') }}" width="117" alt="Cycle for Charity" style="border: 0;margin: 0 auto; display: block;max-width: 200px;height: auto;" class="center-on-narrow">
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <!--[if mso]>
                                        </td>

                                        </tr>
                                        </table>
                                        <![endif]-->
                                    </td>
                                </tr>
                            </table>
                            <!--[if mso]>
                            </td>
                            </tr>
                            </table>
                            <![endif]-->
                        </td>
                    </tr>
                @endif
                <!-- Three Even Columns : END -->

                <tr>
                    <td style="padding: 10px 0 50px 0;width: 100%;font-size: 16px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; mso-height-rule: exactly; line-height:18px; text-align: center; color: {{ (isset($virtual) && $virtual) || (isset($rankings) && $rankings) ? '#ffffff' : '#888888' }};">
                        {{ isset($virtual) && $virtual ? 'Virtual Marathon Series' : (isset($rankings) && $rankings ? 'RunThroughHub' : '© Sport for Charity Group Ltd') }}
                    </td>
                </tr>
            </table>
            <!-- Email Footer : END -->

            <!--[if (gte mso 9)|(IE)]>
            </td>
            </tr>
            </table>
            <![endif]-->
        </div>
    </center>
</td></tr></table>
</body>
</html>
