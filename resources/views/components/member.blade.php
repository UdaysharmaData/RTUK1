<table cellspacing="0" cellpadding="0" role="presentation" width="100%" border="0" align="left">
    <tr>
        <td class="member" >
            <table cellspacing="0" cellpadding="0" role="presentation" width="100%" border="0">
                <tr>
                    <td width="80" valign="middle">
                        <!--[if mso]>
                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="#" style="height:80px;v-text-anchor:middle;width:80px;" arcsize="50%" stroke="f" fill="t">
                            <v:imagedata src="{{ $avatar }}" style="width: 80px; height: 80px; border-radius: 50%;" />
                        </v:roundrect>
                        <![endif]-->
                        <!--[if !mso]><!-->
                      
                        <!--<![endif]-->
                    </td>
                    <td valign="middle">
                        <div style="margin-left: 16px;">
                            <h3 class="text__bold">{{ $name }}</h3>
                            <span>{!! $description !!}</span>
                        </div>
                    </td>
                </tr>
            </table>
            <div class="member__team text__primary" style="line-height: 1.25;">
                {{ $team }}
            </div>
        </td>
    </tr>
</table>