<table align="center">
    <tr>
        <th>{$theme}</th>
    </tr>
    <tr>
        <td align="center">
            <form action="index.php?func=change" method="post" enctype="multipart/form-data">
                <input type="hidden" name="theme" value="{$theme}" />
                <INPUT  type="submit" NAME="submit" VALUE="Select This Theme" onclick="this.form.submit(); this.disabled = 1;" >
            </form>
            <a href="{$theme_image_url}" target="_blank"><img src="{$theme_tn}"></a>
            <br>
            <br>
        </td>
    </tr>
    <tr>
        <td>Author: {$theme_author}</td>
    </tr>
    <tr>
        <td>Website: <a href="{$author_url}" target="_blank">{$author_url}</a></td>
    </tr>
    <tr>
        <td>Version: {$theme_ver}</td>
    </tr>
    <tr>
        <td>Created on: {$author_date}</td>
    </tr>
    <tr>
        <td>Details: </td>
    </tr>
    <tr>
        <td>
            {$theme_details}
        </td>
    <tr>
</table>