<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="utf-8"> <!-- utf-8 works for most cases -->
    <meta name="viewport" content="width=device-width"> <!-- Forcing initial-scale shouldn't be necessary -->
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <title>@yield('title')</title>

    <style type="text/css">
        * {
            /* Stops clients resizing small text. */
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
            -webkit-font-smoothing: antialiased;
        }

        body {
            font-family: Avenir, sans-serif;
            height: 100%;
            width: 100%;
            overflow: auto;
            line-height: 1.4;
        }

        div[style*="margin: 16px 0"] {
            /* Centers email on Android 4.4 */
            margin: 0 !important;
        }

        table,
        td {
            /* Stops Outlook from adding extra spacing to tables. */
            mso-table-lspace: 0 !important;
            mso-table-rspace: 0 !important;
        }

        table {
            border-collapse: collapse;
            border-spacing: 0;
            border-top: 1px solid #EFEFEF;
            width: 100%;
            text-align: left;
            font-size: 14px;
        }

        th,
        td {
            color: #1A1D1F;
            padding: 10px;
            vertical-align: top;
        }

        tr:not(:last-child) {
            border-bottom: 1px solid #EFEFEF;
        }

        tbody tr:nth-child(odd) {
            background-color: #FbFbFb;
        }

        img {
            /* Uses a better rendering method when resizing images in IE. */
            -ms-interpolation-mode: bicubic;
        }

        a {
            color: #2A85FF;
        }

        a[data-site='runthrough.co.uk'] {
            color: #007BC3;
        }

        a[data-site='runninggrandprix.com'] {
            color: #004225;
        }

        a[data-site='leicestershire10k.com'] {
            color: #004225;
        }

        a[x-apple-data-detectors] {
            /* Another work-around for iOS meddling in triggered links. */
            color: inherit !important;
        }

        strong {
            color: #2A85FF;
        }

        strong[data-site='runthrough.co.uk'] {
            color: #007BC3;
        }

        strong[data-site='runninggrandprix.com'] {
            color: #004225;
        }

        strong[data-site='leicestershire10k.com'] {
            color: #004225;
        }

        p,
        span {
            color: #6F767E;
            margin: unset;
        }

        h2,
        h3,
        h4,
        h5 {
            color: #1A1D1F;
            margin: unset;
        }

        h3 {
            font-weight: lighter;
        }

        .content {
            padding: 50px 50px;
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .content__header {
            position: relative;
            border-bottom: 1px solid #EFEFEF;
            padding-bottom: 10px;
        }

        .content__header>img {
            max-height: 70px;
            position: relative;
            right: 0px;
        }

        .content__header h2 {
            position: absolute;
            right: 0px;
            top: 0px;
            margin: unset;
        }

        .content__info {
            display: flex;
            flex-direction: column;
            gap: .3rem;
            width: 100%;
            padding-bottom: 20px;
            padding-top: 20px;
        }

        .bottom__content {
            margin-top: 3rem;
            display: flex;
            flex-direction: column;
            gap: .3rem;
        }

        .bottom__content_2 {
            margin-top: 4rem;
            display: flex;
            flex-direction: column;
            gap: .3rem;
        }

        .pill {
            background-color: #DBDBDB;
            padding: 5px;
            border-radius: 7px;
        }

        .danger {
            background-color: #e35d6a;
            color: #F4F4F4;
        }

        .success {
            background-color: #83BF6E;
            color: #F4F4F4;
        }

        .item {
            display: flex;
            flex-direction: column;
            gap: .2rem;
        }

        .item__title {
            color: #1A1D1F;
        }

        .item__description {
            color: #6F767E;
        }

        .text__center {
            text-align: center;
        }

        .text__left {
            text-align: left;
        }

        .text__bold {
            font-weight: bold;
        }

        .mt-1 {
            margin-top: 1rem;
        }

        .f-12 {
            font-size: 12px !important;
        }

        .break_word {
            max-width: 150px;
            word-wrap: break-word;
            display: block;
        }
    </style>
</head>

<body>
    <div class="content">
        @yield('content')
    </div>
</body>

</html>