<div>
    <table cellspacing="0" cellpadding="0" style="border-collapse: collapse">
    <caption align="top" style="margin-bottom: 10px; font-size: 16px;"><?php echo $table_title;?></caption>
        <tbody>
            <tr>
                <?php foreach ($table_header as $value) :?>
                <td valign="top" style="height: 12.0px; background-color: #bec0bf; border-style: solid; border-width: 1.0px; border-color: #000000; padding: 4.0px; text-align: center;">
                    <p style="margin: 0px; font-stretch: normal; font-size: 16px; line-height: normal; font-family: Helvetica; min-height: 14px;"><?php echo $value ?><br></p>
                </td>
                <?php endforeach;?>
            </tr>
            <?php foreach ($content as $index => $row) :?>
                <?php if(fmod($index, 2) == 0) {
                    $bgcolor = 'background-color: #f5f5f5;';
                } else {
                    $bgcolor = '';
                }?>
            <tr>
                <?php foreach ($row as $value) :?>
                <td valign="top" style="height: 11.0px; border-style: solid; border-width: 1.0px; border-color: #000000; text-align: center; padding: 4.0px; <?php echo $bgcolor ?>">
                    <p style="margin: 0px; font-stretch: normal; font-size: 14px; line-height: normal; font-family: Helvetica; min-height: 14px;"><?php echo $value ?><br></p>
                </td>
                <?php endforeach;?>
            </tr>
            <?php endforeach;?>
        </tbody>
    </table>
</div>
<div style="margin-top: 10px">
    <hr>
    <?php foreach ($desc as $value) :?>
    <p style="margin-top: 5px;margin-bottom: 5px;">※ <?php echo $value ?></p>
    <?php endforeach;?>
</div>