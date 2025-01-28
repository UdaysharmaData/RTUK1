@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')

    @if (isset($passed) && !empty($passed))
    <tbody>

    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web0f251244-eea6-4c67-87c8-324a316886b5" style="height:100%;display:table;background-color:#fff;min-width:100%px" height="100%" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-0f251244-eea6-4c67-87c8-324a316886b5el-text" align="left" style="font-size:13px;line-height:22px;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;padding:15px 50px 15px 50px;color:#636363">
                            <div style="min-height:10px;text-align:center" align="center">
                                <p style="margin:0px"><span style="font-size:18px"><strong>GOOD LUCK!</strong></span></p>
                                <p style="margin:0px">&nbsp;</p>
                                <p style="margin:0px;font-size:14px">Hi {{ ucfirst($user['salutation_name']) }} and thanks for entering your RunThrough event - we can't wait to have you along!</p>
                                <p style="margin:0px;font-size:14px">&nbsp;</p>
                                <p style="margin:0px;font-size:14px">You will receive detailed race information via email around 10 days before the event. If you have any questions in the meantime please see our FAQ page <a href="https://knowledge.runthrough.co.uk/" rel="noopener" style="color:#2672f7;font-weight:normal;text-decoration:underline" target="_blank">HERE</a> or contact us on <a href="mailto:info@runthrough.co.uk" style="color:#2672f7;font-weight:normal;text-decoration:underline" target="_blank">info@runthrough.co.uk</a>.</p>
                                <p style="margin:0px;font-size:14px">&nbsp;</p>
                                <p style="margin:0px;font-size:14px">If you are a wheelchair athlete or have any other requirements, please get in touch with us on&nbsp;<a href="mailto:info@runthrough.co.uk" rel="noopener noreferrer" style="color:#2672f7;font-weight:normal;text-decoration:underline" target="_blank">info@runthrough.co.uk</a>&nbsp;so that we can provide specific advice and instructions.</p>
                                <p style="margin:0px;font-size:14px">&nbsp;</p>
{{--                                <p style="margin:0px;font-size:14px">If you entered the ballot for the world’s first FREE TO ENTER chip-timed, road-closed run at the RunThrough Foundation {{$extraData['passed']['eecs'][0]['name']}}, then please check the information page at <a href="https://u8223458.ct.sendgrid.net/ls/click?upn=u001.96-2BJl3nNBdWBrZ6d5qprJVprSFENnhrLdUHbcq2I9PMQ4hP4eUXZxAIsD-2B0hAKTcYhuXiWKHwi2ZBAqqL4bIX9Ape3aA9wd8KYkgbjpmNyY-3DAZLt_zEsPWJOGaQbvcLqbylV0TSg0e35Azqkw4aHJAzwLi03TLkjFunPEvyeMyXfCYNxB1ERLDQoyQ-2Bktr294EbOkJy1H8FB07SkT6UekE1opPOWo-2Bmcama-2FJ0G-2F7sH0HxKsylLtqMMlpeDJUt6FODOUl-2F2cAd73a4hkeH0-2Fhvr1nchQg-2F8C5DBlCCh7qDidmbjIuv9V2Qa-2FHSLdq5-2BnmoHSMAJhES1F88RdeKLmP8Ju2mkaxjH1Ig0NuLSq8Dscd2BNX6akGGYNXzbHZ8u2-2FYXtMtw-3D-3D" rel="noopener" style="color:#2672f7;font-weight:normal;text-decoration:underline" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://u8223458.ct.sendgrid.net/ls/click?upn%3Du001.96-2BJl3nNBdWBrZ6d5qprJVprSFENnhrLdUHbcq2I9PMQ4hP4eUXZxAIsD-2B0hAKTcYhuXiWKHwi2ZBAqqL4bIX9Ape3aA9wd8KYkgbjpmNyY-3DAZLt_zEsPWJOGaQbvcLqbylV0TSg0e35Azqkw4aHJAzwLi03TLkjFunPEvyeMyXfCYNxB1ERLDQoyQ-2Bktr294EbOkJy1H8FB07SkT6UekE1opPOWo-2Bmcama-2FJ0G-2F7sH0HxKsylLtqMMlpeDJUt6FODOUl-2F2cAd73a4hkeH0-2Fhvr1nchQg-2F8C5DBlCCh7qDidmbjIuv9V2Qa-2FHSLdq5-2BnmoHSMAJhES1F88RdeKLmP8Ju2mkaxjH1Ig0NuLSq8Dscd2BNX6akGGYNXzbHZ8u2-2FYXtMtw-3D-3D&amp;source=gmail&amp;ust=1723117475376000&amp;usg=AOvVaw1pI05_AoUxBOtyEyntUTKn">https://www.runthrough.co.uk/</a> for more details on timings and entry release dates. Please note, there are a limited number of places available and therefore spots will be allocated via a series of ballot waves.</p>--}}
{{--                                <p style="margin:0px;font-size:14px">&nbsp;</p>--}}
{{--                                <p style="margin:0px;font-size:14px">Have you got a runner feature story for us and happy to share it? If so, please fill in <a href="https://u8223458.ct.sendgrid.net/ls/click?upn=u001.96-2BJl3nNBdWBrZ6d5qprJVprSFENnhrLdUHbcq2I9PNZS7ouOLJlWJqDDX2D0DIwbUAHbHW0ziXbKHnWpj1jSH81RnpXxHIHjPae-2BsA2HuE-3DHZJl_zEsPWJOGaQbvcLqbylV0TSg0e35Azqkw4aHJAzwLi03TLkjFunPEvyeMyXfCYNxB1ERLDQoyQ-2Bktr294EbOkJ6f1ADlW0RR7yumB1f3-2BS113Px8dwi2Q4-2B5983eAmBEL268T2c4OpTuw9yFGnoLaGUNOE3pgPjx7zMxzdcMlMEFZ7cz4-2BY5Dicwrkkc57Doo7dUK2zE-2F2DzDsjYn-2BOMumXn8R61h6SDnh2ktyqGJ4xJQEhg1gMIeMGuSu2nTBEJ13Pn7SM8G8S8ut8qQOHZ8Nw-3D-3D" style="color:#2672f7;font-weight:normal;text-decoration:underline" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://u8223458.ct.sendgrid.net/ls/click?upn%3Du001.96-2BJl3nNBdWBrZ6d5qprJVprSFENnhrLdUHbcq2I9PNZS7ouOLJlWJqDDX2D0DIwbUAHbHW0ziXbKHnWpj1jSH81RnpXxHIHjPae-2BsA2HuE-3DHZJl_zEsPWJOGaQbvcLqbylV0TSg0e35Azqkw4aHJAzwLi03TLkjFunPEvyeMyXfCYNxB1ERLDQoyQ-2Bktr294EbOkJ6f1ADlW0RR7yumB1f3-2BS113Px8dwi2Q4-2B5983eAmBEL268T2c4OpTuw9yFGnoLaGUNOE3pgPjx7zMxzdcMlMEFZ7cz4-2BY5Dicwrkkc57Doo7dUK2zE-2F2DzDsjYn-2BOMumXn8R61h6SDnh2ktyqGJ4xJQEhg1gMIeMGuSu2nTBEJ13Pn7SM8G8S8ut8qQOHZ8Nw-3D-3D&amp;source=gmail&amp;ust=1723117475376000&amp;usg=AOvVaw3vS40sLU1uN8voC0Qls_rm">this form</a> to let us know all about it :)</p>--}}
{{--                                <p style="margin:0px;font-size:14px">&nbsp;</p>--}}
                                <p style="margin:0px;font-size:14px">We look forward to seeing you on a start line soon!</p>
                                <p style="margin:0px;font-size:14px">&nbsp;</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524webad422370-f762-4a26-92de-c4cf3878h0oi" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-ad422370-f762-4a26-92de-c4cf3878h0oi-order-item" align="left" style="font-size:13px;line-height:22px;font-family:Helvetica,Roboto,Arial,sans-serif;padding:15px 50px 15px 50px">
                            <div style="min-height:10px;color:#000000">

                                <div>
                                <x-table :headers="$header" :body="$passed"></x-table>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
{{--    <tr>--}}
{{--        <td style="font-family:inherit">--}}
{{--            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web5ccc52d3-25d8-4862-94ac-923017254659" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">--}}
{{--                <tbody>--}}
{{--                    <tr>--}}
{{--                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web5ccc52d3-25d8-4862-94ac-923017254659-img" align="center" style="font-family:inherit;padding:15px 50px 15px 50px">--}}
{{--                            <a href="https://u8223458.ct.sendgrid.net/ls/click?upn=u001.96-2BJl3nNBdWBrZ6d5qprJVprSFENnhrLdUHbcq2I9PMQ4hP4eUXZxAIsD-2B0hAKTcYhuXiWKHwi2ZBAqqL4bIX9Ape3aA9wd8KYkgbjpmNyY-3DvV4f_zEsPWJOGaQbvcLqbylV0TSg0e35Azqkw4aHJAzwLi03TLkjFunPEvyeMyXfCYNxB1ERLDQoyQ-2Bktr294EbOkJ44TcGkGqUQbv94gS-2BiVQo8K0s-2Bnmoqae3chYTbzU5Hdnrk0rAq5a9pXrOKIHF6DqQMOXL4N18ogRH0Mu7uaGxi-2FmMi11TX09LrwqLiSprNMaYSREecuQlH86U4Xgz-2FVa8qJnesi2NOg-2Fv5U4KkYtwvD5206HDlZbuALMLP-2B7BY98r5YldnEEkVDliMTVoF-2F5Q-3D-3D" style="color:#2672f7;font-weight:normal;border:none;text-decoration:none" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://u8223458.ct.sendgrid.net/ls/click?upn%3Du001.96-2BJl3nNBdWBrZ6d5qprJVprSFENnhrLdUHbcq2I9PMQ4hP4eUXZxAIsD-2B0hAKTcYhuXiWKHwi2ZBAqqL4bIX9Ape3aA9wd8KYkgbjpmNyY-3DvV4f_zEsPWJOGaQbvcLqbylV0TSg0e35Azqkw4aHJAzwLi03TLkjFunPEvyeMyXfCYNxB1ERLDQoyQ-2Bktr294EbOkJ44TcGkGqUQbv94gS-2BiVQo8K0s-2Bnmoqae3chYTbzU5Hdnrk0rAq5a9pXrOKIHF6DqQMOXL4N18ogRH0Mu7uaGxi-2FmMi11TX09LrwqLiSprNMaYSREecuQlH86U4Xgz-2FVa8qJnesi2NOg-2Fv5U4KkYtwvD5206HDlZbuALMLP-2B7BY98r5YldnEEkVDliMTVoF-2F5Q-3D-3D&amp;source=gmail&amp;ust=1723117475376000&amp;usg=AOvVaw3o4tZX4IhGCUxrFpS1LMqX">--}}
{{--                                <img border="0" src="https://cdn-bucket-production.s3.eu-west-2.amazonaws.com/email/London_Ten_16x9-1-1-600x338-1.jpg" width="505" height="auto" style="border:none;display:inline-block;font-size:14px;font-weight:bold;height:auto;outline:none;text-decoration:none;text-transform:capitalize;vertical-align:middle;margin-right:10px;max-width:100%" class="CToWUd" data-bit="iit">--}}
{{--                            </a>--}}
{{--                        </td>--}}
{{--                    </tr>--}}
{{--                </tbody>--}}
{{--            </table>--}}
{{--        </td>--}}
{{--    </tr>--}}
{{--    <tr>--}}
{{--        <td style="font-family:inherit">--}}
{{--            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524webb540f90a-8ef2-43c2-a0e4-b9802b8830fc" style="height:100%;display:table;background-color:#fff;min-width:100%px" height="100%" bgcolor="#fff">--}}
{{--                <tbody>--}}
{{--                    <tr>--}}
{{--                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-b540f90a-8ef2-43c2-a0e4-b9802b8830fcel-text" align="left" style="font-size:13px;line-height:22px;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;padding:15px 50px 15px 50px;color:#636363">--}}
{{--                            <div style="min-height:10px;text-align:left" align="left">--}}
{{--                                <p style="margin:0px;text-align:center" align="center"><span style="font-size:18px"><strong>{{$extraData['passed']['eecs'][0]['name']}} by the RunThrough Foundation</strong></span></p>--}}
{{--                                <p style="margin:0px;font-size:14px">&nbsp;</p>--}}
{{--                                <p style="margin:0px;font-size:14px;text-align:center" align="center">Don't forget to get your name in the ballot for London's first ever, free to enter, road-closed 10k - {{$extraData['passed']['eecs'][0]['name']}}.</p>--}}
{{--                            </div>--}}
{{--                        </td>--}}
{{--                    </tr>--}}
{{--                </tbody>--}}
{{--            </table>--}}
{{--        </td>--}}
{{--    </tr>--}}
{{--    <tr>--}}
{{--        <td style="font-family:inherit">--}}
{{--            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web51da5100-2413-4ad0-bd6c-2d12a4a94ebc" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">--}}
{{--                <tbody>--}}
{{--                    <tr>--}}
{{--                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-51da5100-2413-4ad0-bd6c-2d12a4a94ebc-button" style="font-family:inherit;padding:15px 50px 15px 50px">--}}
{{--                            <table align="center" cellspacing="0" cellpadding="0" border="0" width="85%">--}}
{{--                                <tbody>--}}
{{--                                    <tr>--}}
{{--                                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-51da5100-2413-4ad0-bd6c-2d12a4a94ebcbutton" style="font-family:inherit;margin:10px">--}}
{{--                                            <a href="https://u8223458.ct.sendgrid.net/ls/click?upn=u001.96-2BJl3nNBdWBrZ6d5qprJVprSFENnhrLdUHbcq2I9PMQ4hP4eUXZxAIsD-2B0hAKTcYhuXiWKHwi2ZBAqqL4bIX9Ape3aA9wd8KYkgbjpmNyY-3Dlgc1_zEsPWJOGaQbvcLqbylV0TSg0e35Azqkw4aHJAzwLi03TLkjFunPEvyeMyXfCYNxB1ERLDQoyQ-2Bktr294EbOkJ5ASQCz9EZaqnk4QJILLT5h60zVRBR3Hev-2FJDHWYQvdetU-2FjAJQ8IrROiMYBeuwlezlYFDs58K9PAQwPRVJ0rOpZ-2F1c77ktp05ysyCd3Wt4VaVDo3taJaCYba8fcwGt5Zp4-2FVK4SAv1mEKiVS7VKi5m5czo2NCJmgECghF-2Bt1d-2B1tkpf3TG2gWoBpmER9cQ3Aw-3D-3D" style="line-height:21px;text-align:center;text-decoration:none;margin:0px;display:block;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;background-color:#007bc3;font-size:13px;color:#fff;font-weight:normal;padding:12px 20px 12px 20px;border-radius:5px 5px 5px 5px" bgcolor="#007BC3" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://u8223458.ct.sendgrid.net/ls/click?upn%3Du001.96-2BJl3nNBdWBrZ6d5qprJVprSFENnhrLdUHbcq2I9PMQ4hP4eUXZxAIsD-2B0hAKTcYhuXiWKHwi2ZBAqqL4bIX9Ape3aA9wd8KYkgbjpmNyY-3Dlgc1_zEsPWJOGaQbvcLqbylV0TSg0e35Azqkw4aHJAzwLi03TLkjFunPEvyeMyXfCYNxB1ERLDQoyQ-2Bktr294EbOkJ5ASQCz9EZaqnk4QJILLT5h60zVRBR3Hev-2FJDHWYQvdetU-2FjAJQ8IrROiMYBeuwlezlYFDs58K9PAQwPRVJ0rOpZ-2F1c77ktp05ysyCd3Wt4VaVDo3taJaCYba8fcwGt5Zp4-2FVK4SAv1mEKiVS7VKi5m5czo2NCJmgECghF-2Bt1d-2B1tkpf3TG2gWoBpmER9cQ3Aw-3D-3D&amp;source=gmail&amp;ust=1723117475376000&amp;usg=AOvVaw25krzl1u95oyQX2PyuZNIV"><span style="text-align:center;vertical-align:middle;line-height:21px;color:#fff">ENTER NOW</span></a>--}}
{{--                                        </td>--}}
{{--                                    </tr>--}}
{{--                                </tbody>--}}
{{--                            </table>--}}
{{--                        </td>--}}
{{--                    </tr>--}}
{{--                </tbody>--}}
{{--            </table>--}}
{{--        </td>--}}
{{--    </tr>--}}
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web19f81aec-f2ad-4206-aef3-6dd3efb7dede" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web19f81aec-f2ad-4206-aef3-6dd3efb7dede-img" align="center" style="font-family:inherit;padding:15px 50px 15px 50px">
                            <a href="https://www.runthrough.co.uk/partner/sportsshoes" target="_blank" style="color:#2672f7;font-weight:normal;border:none;text-decoration:none">
                                <img border="0" src="https://cdn-bucket-production.s3.eu-west-2.amazonaws.com/email/Screenshot-2024-07-01-at-14.39.30.png" width="505" height="auto" style="border:none;display:inline-block;font-size:14px;font-weight:bold;height:auto;outline:none;text-decoration:none;text-transform:capitalize;vertical-align:middle;margin-right:10px;max-width:100%" class="CToWUd" data-bit="iit">
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524webfd45659a-796b-4e4b-bb76-f0c660c4bfe8" style="height:100%;display:table;background-color:#fff;min-width:100%px" height="100%" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-fd45659a-796b-4e4b-bb76-f0c660c4bfe8el-text" align="left" style="font-size:13px;line-height:22px;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;padding:15px 50px 15px 50px;color:#636363">
                            <div style="min-height:10px;text-align:left" align="left">
{{--                                <div style="text-align:center" align="center">Use code <strong>L6450VF6</strong>&nbsp;at the checkout for 15% OFF new season Autumn/ Winter 2024 ranges at&nbsp;<span><a href="https://u8223458.ct.sendgrid.net/ls/click?upn=u001.96-2BJl3nNBdWBrZ6d5qprJeYplsI0YNnPhXnxq90k2f4Mxk-2BGmWBqRu33wOMjkUCKZUUb_zEsPWJOGaQbvcLqbylV0TSg0e35Azqkw4aHJAzwLi03TLkjFunPEvyeMyXfCYNxB1ERLDQoyQ-2Bktr294EbOkJ7em5mDrQmFcDppcQMEcwU07N4UJAi8xTb5AaH8qIkGgN-2FtHUSc6UiU0EcZy4HejqfmuK5k7IeX5KwmxvvlGJ8rLPV4dbBi-2FzRqwHa71uIHZKVBU-2Brl8GUwzuvGNnJYGdjbiyGkY285qRdgpUygKSQkpEoP4XphiwmAtAH0ZZZXMRZTRAdWayngvVpLpQwxFeQ-3D-3D" rel="noopener noreferrer" style="color:#2672f7;font-weight:normal;text-decoration:underline" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://u8223458.ct.sendgrid.net/ls/click?upn%3Du001.96-2BJl3nNBdWBrZ6d5qprJeYplsI0YNnPhXnxq90k2f4Mxk-2BGmWBqRu33wOMjkUCKZUUb_zEsPWJOGaQbvcLqbylV0TSg0e35Azqkw4aHJAzwLi03TLkjFunPEvyeMyXfCYNxB1ERLDQoyQ-2Bktr294EbOkJ7em5mDrQmFcDppcQMEcwU07N4UJAi8xTb5AaH8qIkGgN-2FtHUSc6UiU0EcZy4HejqfmuK5k7IeX5KwmxvvlGJ8rLPV4dbBi-2FzRqwHa71uIHZKVBU-2Brl8GUwzuvGNnJYGdjbiyGkY285qRdgpUygKSQkpEoP4XphiwmAtAH0ZZZXMRZTRAdWayngvVpLpQwxFeQ-3D-3D&amp;source=gmail&amp;ust=1723117475376000&amp;usg=AOvVaw3Ej7ySIbG3namhhNkD6VEp">SportsShoes.com</a></span>*</div>--}}
                                <div style="text-align:center" align="center">Shop Here, Sign up to the RunThrough Newsletter for your 15% code. Scroll down on our homepage <span><a href="https://www.runthrough.co.uk/" rel="noopener noreferrer" style="color:#2672f7;font-weight:normal;text-decoration:underline" target="_blank">Here</a></span> to sign up.</div>
                                <div style="text-align:center" align="center"><span><a href="https://runthrough.co.uk/partner/sportsshoes" rel="noopener noreferrer" style="color:#2672f7;font-weight:normal;text-decoration:underline" target="_blank">Click Here</a></span> for more information on Sports Shoes.</div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web93fd7d36-f1c6-48e0-8237-6bc34308bf3c" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-93fd7d36-f1c6-48e0-8237-6bc34308bf3c-button" style="font-family:inherit;padding:15px 50px 15px 50px">
                            <table align="center" cellspacing="0" cellpadding="0" border="0" width="85%">
                                <tbody>
                                    <tr>
                                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-93fd7d36-f1c6-48e0-8237-6bc34308bf3cbutton" style="font-family:inherit;margin:10px">
                                            <a href="https://u8223458.ct.sendgrid.net/ls/click?upn=u001.96-2BJl3nNBdWBrZ6d5qprJeYplsI0YNnPhXnxq90k2f7C2uOZwI-2B68Yl0MnH6KZFmeDJSMZVWcM9hzNUXs79f1ElHYG4Nm4m0Qu2wne5nZVm1sWujtAuKOzUtwn6nYBY74ipGHNQCwTG9sr-2Bh9QOsFg-3D-3DCt-N_zEsPWJOGaQbvcLqbylV0TSg0e35Azqkw4aHJAzwLi03TLkjFunPEvyeMyXfCYNxB1ERLDQoyQ-2Bktr294EbOkJ5DKJz1sjzUDcJoLGrvz1ZR8QcMR-2Fn5pfIhkyxor1dr-2F8oUUvztIGZL-2BdMxSlUht7tZd7JaMqFdC9hE3ZHsAnVjehLgke64HBzLgO4J8hxK0jBIvM6rOX-2BxrprdpAJ1vRwEjZf-2BGYF8d914sUYTkE5-2BFl6qDu7BeyeqCIllncumWswgYo0SAyzi-2FvcynrrwAmg-3D-3D" style="line-height:21px;text-align:center;text-decoration:none;margin:0px;display:block;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;background-color:#007bc3;font-size:13px;color:#fff;font-weight:normal;padding:12px 20px 12px 20px;border-radius:5px 5px 5px 5px" bgcolor="#007BC3" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://u8223458.ct.sendgrid.net/ls/click?upn%3Du001.96-2BJl3nNBdWBrZ6d5qprJeYplsI0YNnPhXnxq90k2f7C2uOZwI-2B68Yl0MnH6KZFmeDJSMZVWcM9hzNUXs79f1ElHYG4Nm4m0Qu2wne5nZVm1sWujtAuKOzUtwn6nYBY74ipGHNQCwTG9sr-2Bh9QOsFg-3D-3DCt-N_zEsPWJOGaQbvcLqbylV0TSg0e35Azqkw4aHJAzwLi03TLkjFunPEvyeMyXfCYNxB1ERLDQoyQ-2Bktr294EbOkJ5DKJz1sjzUDcJoLGrvz1ZR8QcMR-2Fn5pfIhkyxor1dr-2F8oUUvztIGZL-2BdMxSlUht7tZd7JaMqFdC9hE3ZHsAnVjehLgke64HBzLgO4J8hxK0jBIvM6rOX-2BxrprdpAJ1vRwEjZf-2BGYF8d914sUYTkE5-2BFl6qDu7BeyeqCIllncumWswgYo0SAyzi-2FvcynrrwAmg-3D-3D&amp;source=gmail&amp;ust=1723117475376000&amp;usg=AOvVaw2pKw_IcuqgY1pxSu1iOCwM"><span style="text-align:center;vertical-align:middle;line-height:21px;color:#fff">SHOP HERE</span></a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web0ba407db-687c-479d-a0f7-12358c0d416b" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web0ba407db-687c-479d-a0f7-12358c0d416b-img" align="center" style="font-family:inherit;padding:15px 50px 15px 50px">
                            <a href="https://www.finalsurge.com/coach/NewLevelsCoaching/training/runthrough" style="color:#2672f7;font-weight:normal;border:none;text-decoration:none" target="_blank" >
                                <img border="0" src="https://cdn-bucket-production.s3.eu-west-2.amazonaws.com/email/Final-Runthrough-Emailer-Image-.png" width="505" height="auto" style="border:none;display:inline-block;font-size:14px;font-weight:bold;height:auto;outline:none;text-decoration:none;text-transform:capitalize;vertical-align:middle;margin-right:10px;max-width:100%" class="CToWUd" data-bit="iit">
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web7133ea45-dc6f-4360-9e57-57358213d8a2" style="height:100%;display:table;background-color:#fff;min-width:100%px" height="100%" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-7133ea45-dc6f-4360-9e57-57358213d8a2el-text" align="left" style="font-size:13px;line-height:22px;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;padding:15px 50px 15px 50px;color:#636363">
                            <div style="min-height:10px;text-align:center" align="center">
                                <p style="margin:0px"><strong>GET RACE READY with RUNTHROUGH TRAINING PLANS powered by </strong></p>
                                <p style="margin:0px"><strong>New Levels Coaching.</strong></p>
                                <p style="margin:0px"><br>Whether you’re racing for the podium or embracing the joy of every mile, your journey is uniquely yours.<br>With your training plan, you’ll get a plan written by our expert coaches NOT AI and real NLC coach support as you train towards your next race.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524webd0692ee6-9e95-41fb-901d-08b9ca55ace8" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-d0692ee6-9e95-41fb-901d-08b9ca55ace8-button" style="font-family:inherit;padding:15px 50px 15px 50px">
                            <table align="center" cellspacing="0" cellpadding="0" border="0" width="85%">
                                <tbody>
                                    <tr>
                                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-d0692ee6-9e95-41fb-901d-08b9ca55ace8button" style="font-family:inherit;margin:10px">
                                            <a href="https://www.runthrough.co.uk/partner/new-levels-coaching" style="line-height:21px;text-align:center;text-decoration:none;margin:0px;display:block;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;background-color:#007bc3;font-size:13px;color:#fff;font-weight:normal;padding:12px 20px 12px 20px;border-radius:5px 5px 5px 5px" bgcolor="#007BC3" target="_blank" ><span style="text-align:center;vertical-align:middle;line-height:21px;color:#fff">SIGN UP NOW</span></a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524webfc57e3a4-9d76-4d48-bbe7-f139a8264aff" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524webfc57e3a4-9d76-4d48-bbe7-f139a8264aff-img" align="center" style="font-family:inherit;padding:15px 50px 15px 50px">
                            <a href="https://runthrough.co.uk/partner/love-corn" style="color:#2672f7;font-weight:normal;border:none;text-decoration:none" target="_blank">
                                <img border="0" src="https://cdn-bucket-production.s3.eu-west-2.amazonaws.com/email/Email-Assets-1920-x-1080-1.png" width="505" height="auto" style="border:none;display:inline-block;font-size:14px;font-weight:bold;height:auto;outline:none;text-decoration:none;text-transform:capitalize;vertical-align:middle;margin-right:10px;max-width:100%" class="CToWUd" data-bit="iit">
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524webe910a61e-f02d-4e3d-9b7f-76f0d6f805e8" style="height:100%;display:table;background-color:#fff;min-width:100%px" height="100%" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-e910a61e-f02d-4e3d-9b7f-76f0d6f805e8el-text" align="left" style="font-size:13px;line-height:22px;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;padding:15px 50px 15px 50px;color:#636363">
                            <div style="min-height:10px;text-align:center" align="center">
                                <p style="margin:0px"><strong>In life and in snacks, it’s all about finding love in the little things.</strong></p>
                                <p style="margin:0px">Whole corn kernels roasted off the cob to make simply delicious crunchy corn snacks. Get them in shops and online. Give them a crunch. They’re a little bit life changing.</p>
                                <p style="margin:0px"><strong>&nbsp;</strong></p>
                                <p style="margin:0px">Use code<strong> ‘RUNTHROUGH20’ </strong>to get 20% off all LOVE CORN on Amazon.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524webadf773ce-333b-4ba5-a980-d1d826c1b2e4" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-adf773ce-333b-4ba5-a980-d1d826c1b2e4-button" style="font-family:inherit;padding:15px 50px 15px 50px">
                            <table align="center" cellspacing="0" cellpadding="0" border="0" width="85%">
                                <tbody>
                                    <tr>
                                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-adf773ce-333b-4ba5-a980-d1d826c1b2e4button" style="font-family:inherit;margin:10px">
                                            <a href="https://u8223458.ct.sendgrid.net/ls/click?upn=u001.96-2BJl3nNBdWBrZ6d5qprJY8JUAFNKAf3C6LW-2BezfHLb3wvwuugWcSOZf5DoUFpU67tGBuIcSM1K3N6k5Vd2I22FChwbFQvcUtHrsSKlsyc69pCf3YUy9MPYqJO-2FXoRSEtgXv_zEsPWJOGaQbvcLqbylV0TSg0e35Azqkw4aHJAzwLi03TLkjFunPEvyeMyXfCYNxB1ERLDQoyQ-2Bktr294EbOkJ31RgkwVUORAqtbEMzSn2-2FgMPHNYYVZNFgLbApYnMB2yGYOBKlncnASgQ4CwsubR4BugmPrfY-2BxXoUWpAkf46okZOPWeXBfgJX-2FnLWl4JgwlIZAKNuiqi5zjRFGqxOqTqb9gEPkT6EU05ZHiNQthBYqrfQIGYFGw3ZUB9NyR3Vip185siwUD8JkR9-2Fg2kNausA-3D-3D" style="line-height:21px;text-align:center;text-decoration:none;margin:0px;display:block;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;background-color:#007bc3;font-size:13px;color:#fff;font-weight:normal;padding:12px 20px 12px 20px;border-radius:5px 5px 5px 5px" bgcolor="#007BC3" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://u8223458.ct.sendgrid.net/ls/click?upn%3Du001.96-2BJl3nNBdWBrZ6d5qprJY8JUAFNKAf3C6LW-2BezfHLb3wvwuugWcSOZf5DoUFpU67tGBuIcSM1K3N6k5Vd2I22FChwbFQvcUtHrsSKlsyc69pCf3YUy9MPYqJO-2FXoRSEtgXv_zEsPWJOGaQbvcLqbylV0TSg0e35Azqkw4aHJAzwLi03TLkjFunPEvyeMyXfCYNxB1ERLDQoyQ-2Bktr294EbOkJ31RgkwVUORAqtbEMzSn2-2FgMPHNYYVZNFgLbApYnMB2yGYOBKlncnASgQ4CwsubR4BugmPrfY-2BxXoUWpAkf46okZOPWeXBfgJX-2FnLWl4JgwlIZAKNuiqi5zjRFGqxOqTqb9gEPkT6EU05ZHiNQthBYqrfQIGYFGw3ZUB9NyR3Vip185siwUD8JkR9-2Fg2kNausA-3D-3D&amp;source=gmail&amp;ust=1723117475376000&amp;usg=AOvVaw3TzD6Qek1JjtjfP4j9lR2u"><span style="text-align:center;vertical-align:middle;line-height:21px;color:#fff">GET 20% OFF NOW</span></a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web1bc0c576-6a3a-4c95-b1aa-bd02fa94fae5" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web1bc0c576-6a3a-4c95-b1aa-bd02fa94fae5-img" align="center" style="font-family:inherit;padding:15px 50px 15px 50px">
                            <a href="https://www.runthrough.co.uk/partner/brooks" style="color:#2672f7;font-weight:normal;border:none;text-decoration:none" target="_blank">
                                <img border="0" src="https://cdn-bucket-production.s3.eu-west-2.amazonaws.com/email/Discover-best-cushioned-shoes-2-2.jpg" width="505" height="auto" style="border:none;display:inline-block;font-size:14px;font-weight:bold;height:auto;outline:none;text-decoration:none;text-transform:capitalize;vertical-align:middle;margin-right:10px;max-width:100%" class="CToWUd" data-bit="iit">
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web2e2e5cd6-83c8-48fd-a0f7-81262a8cba6d" style="height:100%;display:table;background-color:#fff;min-width:100%px" height="100%" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-2e2e5cd6-83c8-48fd-a0f7-81262a8cba6del-text" align="left" style="font-size:13px;line-height:22px;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;padding:15px 50px 15px 50px;color:#636363">
                            <div style="min-height:10px;text-align:center" align="center">
                                <p style="margin:0px;text-align:center" align="center"><strong>Brooks</strong></p>
                                <p style="margin:0px">&nbsp;</p>
                                <p style="margin:0px">Cushioned running shoes can give you a soft and comfortable ride while offering extra protection and support - so you can focus on your run. Keeping your feet comfy and happy mile after mile!</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524webd257af07-ee35-46c1-914b-68cdd11e2c3b" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-d257af07-ee35-46c1-914b-68cdd11e2c3b-button" style="font-family:inherit;padding:15px 50px 15px 50px">
                            <table align="center" cellspacing="0" cellpadding="0" border="0" width="85%">
                                <tbody>
                                    <tr>
                                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-d257af07-ee35-46c1-914b-68cdd11e2c3bbutton" style="font-family:inherit;margin:10px">
                                            <a href="https://u8223458.ct.sendgrid.net/ls/click?upn=u001.96-2BJl3nNBdWBrZ6d5qprJQ1njejsHXbmolQ5FkZEdPosKVWkcY-2Fk-2FcaRFX7BqGtTwLLIqbfIPU95eCTuQ05CQ9hq-2BpxiZb1Sy3NQeWUrQZRaU5Tx2mR3s0Q1qvAlzpNdWwVoxC7iUg9WfFVlzUua42A8-2FkykJI0LjaW6Iw1COpl3tEqAkCIWN4BGmTDdoFVqm-2BJPQNNZltb4eAabAY4md20sUgypor4EKivdsHVLg88-3DrDiN_zEsPWJOGaQbvcLqbylV0TSg0e35Azqkw4aHJAzwLi03TLkjFunPEvyeMyXfCYNxB1ERLDQoyQ-2Bktr294EbOkJ3TTNVtNxJXCg929ZwwpoB3Z7H3rlmHFJ6cMtF067FnBOEloJinMrp2v6RmcoyPoY90SVEP8H-2B0mBIMBuUV49507OisJKbpcJWdigHtNsVQuM90XUYkk0-2FuSDGXFTVO5oWXHCRYNR8PmGjaI9pNdgj2YfkrO1EymoHy-2B8qrMKevGN8v-2Bw97tfHMXZWTjE3uT2g-3D-3D" style="line-height:21px;text-align:center;text-decoration:none;margin:0px;display:block;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;background-color:#007bc3;font-size:13px;color:#fff;font-weight:normal;padding:12px 20px 12px 20px;border-radius:5px 5px 5px 5px" bgcolor="#007BC3" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://u8223458.ct.sendgrid.net/ls/click?upn%3Du001.96-2BJl3nNBdWBrZ6d5qprJQ1njejsHXbmolQ5FkZEdPosKVWkcY-2Fk-2FcaRFX7BqGtTwLLIqbfIPU95eCTuQ05CQ9hq-2BpxiZb1Sy3NQeWUrQZRaU5Tx2mR3s0Q1qvAlzpNdWwVoxC7iUg9WfFVlzUua42A8-2FkykJI0LjaW6Iw1COpl3tEqAkCIWN4BGmTDdoFVqm-2BJPQNNZltb4eAabAY4md20sUgypor4EKivdsHVLg88-3DrDiN_zEsPWJOGaQbvcLqbylV0TSg0e35Azqkw4aHJAzwLi03TLkjFunPEvyeMyXfCYNxB1ERLDQoyQ-2Bktr294EbOkJ3TTNVtNxJXCg929ZwwpoB3Z7H3rlmHFJ6cMtF067FnBOEloJinMrp2v6RmcoyPoY90SVEP8H-2B0mBIMBuUV49507OisJKbpcJWdigHtNsVQuM90XUYkk0-2FuSDGXFTVO5oWXHCRYNR8PmGjaI9pNdgj2YfkrO1EymoHy-2B8qrMKevGN8v-2Bw97tfHMXZWTjE3uT2g-3D-3D&amp;source=gmail&amp;ust=1723117475376000&amp;usg=AOvVaw3gi52R1PcomBDZt3i1Xs1i"><span style="text-align:center;vertical-align:middle;line-height:21px;color:#fff">DISCOVER THE BEST CUSHIONED RUNNING SHOES</span></a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web8429b258-aba3-421d-a33f-9e70511ae763" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web8429b258-aba3-421d-a33f-9e70511ae763-img" align="center" style="font-family:inherit;padding:15px 50px 15px 50px">
                            <a href="https://www.runthrough.co.uk/partner/nuun" style="color:#2672f7;font-weight:normal;border:none;text-decoration:none" target="_blank">
                                <img border="0" src="https://cdn-bucket-production.s3.eu-west-2.amazonaws.com/email/Nuun_Runthrough_email_Banner_1920x1080px_1_v1.jpg" width="505" height="auto" style="border:none;display:inline-block;font-size:14px;font-weight:bold;height:auto;outline:none;text-decoration:none;text-transform:capitalize;vertical-align:middle;margin-right:10px;max-width:100%" class="CToWUd" data-bit="iit">
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web9eb0d4a0-cc29-4e7f-a35e-721a276583bd" style="height:100%;display:table;background-color:#fff;min-width:100%px" height="100%" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-9eb0d4a0-cc29-4e7f-a35e-721a276583bdel-text" align="left" style="font-size:13px;line-height:22px;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;padding:15px 50px 15px 50px;color:#636363">
                            <div style="min-height:10px;text-align:center" align="center">
                                <p style="margin:0px"><strong>Fuel your run with Nuun!&nbsp;</strong></p>
                                <p style="margin:0px">&nbsp;</p>
                                <p style="margin:0px">As the Official Hydration Partner of RunThrough, we're here to help you compete and complete your run.&nbsp;</p>
                                <p style="margin:0px">Our effervescent drink tablets deliver a clean formulation for peak performance helping you to be at your best whenever you run!&nbsp;&nbsp;</p>
                                <p style="margin:0px"><strong>Hydration starts with Nuun. #NuunHydration</strong></p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web0f69d503-f4bb-402b-885b-23c96d08deae" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-0f69d503-f4bb-402b-885b-23c96d08deae-button" style="font-family:inherit;padding:15px 50px 15px 50px">
                            <table align="center" cellspacing="0" cellpadding="0" border="0" width="85%">
                                <tbody>
                                    <tr>
                                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-0f69d503-f4bb-402b-885b-23c96d08deaebutton" style="font-family:inherit;margin:10px">
                                            <a href="https://u8223458.ct.sendgrid.net/ls/click?upn=u001.96-2BJl3nNBdWBrZ6d5qprJc-2FM4x6KJOCrMY920bk8opX0qK5dU58uiNeZ-2B6eVYvfCqYlzIoZtJd0V3pOkg-2FBwtA-3D-3DNCLF_zEsPWJOGaQbvcLqbylV0TSg0e35Azqkw4aHJAzwLi03TLkjFunPEvyeMyXfCYNxB1ERLDQoyQ-2Bktr294EbOkJ2Qfs8-2F4fO6kgHgSquOYzBb9qnVUlmx9cJwmLmDXDUV4eQBnrLitzZZZBGSUdmV2zwJK2UCgjAnvDGDe-2Fyq6ywIqH1DzR2IrEIwcm9JsGkTAk8UaUDM4RrmNl8va5L7bHqC43QH0qF-2FT9L6PR7Dpdq7Z4wphXjaSYdol9l8UkGC0ESKvna50wWbKok0MhRiGgg-3D-3D" style="line-height:21px;text-align:center;text-decoration:none;margin:0px;display:block;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;background-color:#007bc3;font-size:13px;color:#fff;font-weight:normal;padding:12px 20px 12px 20px;border-radius:5px 5px 5px 5px" bgcolor="#007BC3" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://u8223458.ct.sendgrid.net/ls/click?upn%3Du001.96-2BJl3nNBdWBrZ6d5qprJc-2FM4x6KJOCrMY920bk8opX0qK5dU58uiNeZ-2B6eVYvfCqYlzIoZtJd0V3pOkg-2FBwtA-3D-3DNCLF_zEsPWJOGaQbvcLqbylV0TSg0e35Azqkw4aHJAzwLi03TLkjFunPEvyeMyXfCYNxB1ERLDQoyQ-2Bktr294EbOkJ2Qfs8-2F4fO6kgHgSquOYzBb9qnVUlmx9cJwmLmDXDUV4eQBnrLitzZZZBGSUdmV2zwJK2UCgjAnvDGDe-2Fyq6ywIqH1DzR2IrEIwcm9JsGkTAk8UaUDM4RrmNl8va5L7bHqC43QH0qF-2FT9L6PR7Dpdq7Z4wphXjaSYdol9l8UkGC0ESKvna50wWbKok0MhRiGgg-3D-3D&amp;source=gmail&amp;ust=1723117475376000&amp;usg=AOvVaw1L6KS2NXuUCReduWejdUai"><span style="text-align:center;vertical-align:middle;line-height:21px;color:#fff">FIND OUT MORE</span></a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web8429b258-aba3-421d-a33f-9e70511ae763" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">
                <tbody>
                <tr>
                    <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web8429b258-aba3-421d-a33f-9e70511ae763-img" align="center" style="font-family:inherit;padding:15px 50px 15px 50px">
                        <a href="#m_8538456141233861889_m_-3124875294357181132_m_-1949024433886333524_" style="color:#2672f7;font-weight:normal;border:none;text-decoration:none">
                            <img border="0" src="https://cdn-bucket-production.s3.eu-west-2.amazonaws.com/email/event_win_image.png" width="505" height="auto" style="border:none;display:inline-block;font-size:14px;font-weight:bold;height:auto;outline:none;text-decoration:none;text-transform:capitalize;vertical-align:middle;margin-right:10px;max-width:100%" class="CToWUd" data-bit="iit">
                        </a>
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web9eb0d4a0-cc29-4e7f-a35e-721a276583bd" style="height:100%;display:table;background-color:#fff;min-width:100%px" height="100%" bgcolor="#fff">
                <tbody>
                <tr>
                    <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-9eb0d4a0-cc29-4e7f-a35e-721a276583bdel-text" align="left" style="font-size:13px;line-height:22px;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;padding:15px 50px 15px 50px;color:#636363">
                        <div style="min-height:10px;text-align:center" align="center">
                            <p style="margin:0px">Support a charity of your choosing by setting up a fundraising page on givestar today! Make every step count for a cause close to your heart and win a running bundle worth over £460, including Brooks shoes! T&Cs apply.&nbsp;</p>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web0f69d503-f4bb-402b-885b-23c96d08deae" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">
                <tbody>
                <tr>
                    <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-0f69d503-f4bb-402b-885b-23c96d08deae-button" style="font-family:inherit;padding:15px 50px 15px 50px">
                        <table align="center" cellspacing="0" cellpadding="0" border="0" width="85%">
                            <tbody>
                            <tr>
                                <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web-0f69d503-f4bb-402b-885b-23c96d08deaebutton" style="font-family:inherit;margin:10px">
                                    <a href="https://info.givestar.io/givestar-x-runthrough" style="line-height:21px;text-align:center;text-decoration:none;margin:0px;display:block;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;background-color:#007bc3;font-size:13px;color:#fff;font-weight:normal;padding:12px 20px 12px 20px;border-radius:5px 5px 5px 5px" bgcolor="#007BC3" target="_blank"><span style="text-align:center;vertical-align:middle;line-height:21px;color:#fff">START FUNDRAISING</span></a>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524webcf208911-1ce4-416a-a908-bf23b50e9997" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524webcf208911-1ce4-416a-a908-bf23b50e9997-img" align="center" style="font-family:inherit;text-align:center;padding:5px 0px 5px 0px">
                            <a href="https://www.runthrough.co.uk/partner/sportsshoes" style="color:#2672f7;font-weight:normal;border:none;text-decoration:none" target="_blank" >
                                <img border="0" src="https://cdn-bucket-production.s3.eu-west-2.amazonaws.com/email/Screenshot-2024-03-11-at-15.10.41.png" width="390" height="auto" style="border:none;display:inline-block;font-size:14px;font-weight:bold;height:auto;outline:none;text-decoration:none;text-transform:capitalize;vertical-align:middle;margin-right:10px;max-width:100%" class="CToWUd" data-bit="iit">
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web706659c8-a662-4788-81b4-e8c9ae28ac03" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">
                <tbody>
                <tr>
                    <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web706659c8-a662-4788-81b4-e8c9ae28ac03-img" align="center" style="font-family:inherit;text-align:center;padding:5px 0px 5px 0px">
                        <a href="https://www.runthrough.co.uk/partner/brooks" style="color:#2672f7;font-weight:normal;border:none;text-decoration:none" target="_blank">
                            <img border="0" src="https://cdn-bucket-production.s3.eu-west-2.amazonaws.com/email/Screenshot-2024-03-11-at-15.14.25.png" width="234" height="auto" style="border:none;display:inline-block;font-size:14px;font-weight:bold;height:auto;outline:none;text-decoration:none;text-transform:capitalize;vertical-align:middle;margin-right:10px;max-width:100%" class="CToWUd" data-bit="iit">
                        </a>
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web7081c10f-0dce-4917-ae8e-5e3575a0de18" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web7081c10f-0dce-4917-ae8e-5e3575a0de18-img" align="center" style="font-family:inherit;text-align:center;padding:5px 0px 5px 0px">
                            <a href="https://www.runthrough.co.uk/partner/nuun" style="color:#2672f7;font-weight:normal;border:none;text-decoration:none" target="_blank">
                                <img border="0" src="https://cdn-bucket-production.s3.eu-west-2.amazonaws.com/email/Screenshot-2024-03-11-at-15.12.13.png" width="265" height="auto" style="border:none;display:inline-block;font-size:14px;font-weight:bold;height:auto;outline:none;text-decoration:none;text-transform:capitalize;vertical-align:middle;margin-right:10px;max-width:100%" class="CToWUd" data-bit="iit">
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web6cb08536-04a4-46d5-8034-d4c5d517134b" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web6cb08536-04a4-46d5-8034-d4c5d517134b-img" align="center" style="font-family:inherit;text-align:center;padding:5px 0px 5px 0px">
                            <a href="https://info.givestar.io/givestar-x-runthrough" style="color:#2672f7;font-weight:normal;border:none;text-decoration:none" target="_blank">
                                <img border="0" src="https://cdn-bucket-production.s3.eu-west-2.amazonaws.com/email/givestar2024.jpg" width="247" height="auto" style="border:none;display:inline-block;font-size:14px;font-weight:bold;height:auto;outline:none;text-decoration:none;text-transform:capitalize;vertical-align:middle;margin-right:10px;max-width:100%" class="CToWUd" data-bit="iit">
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524webdfbe4bef-5c19-4dcd-a898-146213941631" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524webdfbe4bef-5c19-4dcd-a898-146213941631-img" align="center" style="font-family:inherit;text-align:center;padding:5px 0px 5px 0px">
                            <a href="https://www.runthrough.co.uk/partner/love-corn" style="color:#2672f7;font-weight:normal;border:none;text-decoration:none" target="_blank">
                                <img border="0" src="https://cdn-bucket-production.s3.eu-west-2.amazonaws.com/email/Screenshot-2024-03-11-at-15.14.41.png" width="182" height="auto" style="border:none;display:inline-block;font-size:14px;font-weight:bold;height:auto;outline:none;text-decoration:none;text-transform:capitalize;vertical-align:middle;margin-right:10px;max-width:100%" class="CToWUd" data-bit="iit">
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-family:inherit">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web6845459f-f6b9-4298-928f-e5ab7cc430e6" style="display:table;background-color:#fff;min-width:100%px" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td id="m_8538456141233861889m_-3124875294357181132m_-1949024433886333524web6845459f-f6b9-4298-928f-e5ab7cc430e6-img" align="center" style="font-family:inherit;text-align:center;padding:5px 0px 5px 0px">
                            <a href="https://www.runthrough.co.uk/partner/new-levels-coaching" style="color:#2672f7;font-weight:normal;border:none;text-decoration:none" target="_blank">
                                <img border="0" src="https://cdn-bucket-production.s3.eu-west-2.amazonaws.com/email/Screenshot-2024-03-11-at-15.14.54.png" width="197" height="auto" style="border:none;display:inline-block;font-size:14px;font-weight:bold;height:auto;outline:none;text-decoration:none;text-transform:capitalize;vertical-align:middle;margin-right:10px;max-width:100%" class="CToWUd" data-bit="iit">
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>

</tbody>

    @else
        @if ((!isset($refunds) || (isset($refunds) && empty($refunds))) && isset($failed) && !empty($failed))
            <p>
                You could not be registered to the events below:
            </p>

            {{-- Failed Order Summary section --}}
            <div class="my-1">
                <h3>Order Summary</h3>
                <x-table :headers="$header" :body="$failed"></x-table>
            </div>
        @endif
    @endif

    @if (isset($refunds) && !empty($refunds))
        <p>
            @if (!empty($passed))
                You have not been registered fully yet, because you could not be registered to the events below
            @else
                You could not be registered to the events below
            @endif
            and have been refunded <strong class="dark">{{$refundedAmount}}</strong> for them.
        </p>

        {{-- Refunded Order Summary section --}}
        <div class="my-1">
            <h3>Order Summary</h3>
            <x-table :headers="$header" :body="$refunds"></x-table>
        </div>
    @endif

    @if (isset($extraData['wasRecentlyCreated']) && $extraData['wasRecentlyCreated'])


        @if (isset($passed) && !empty($passed))
            <!-- <p>
                To complete your registration, please click
                <a href="{{ $mailHelper->portalLink('entries') }}"><strong>Here</strong></a>.
            </p> -->
        @endif
    @else

    @endif
@endsection
