<!-- <div class="pager">
    <form class="awpcp-pagination-form" method="get">
        <?php echo awpcp_html_hidden_fields( $params ); ?>
        <table>
            <tbody>
                <tr>
                    <td><?php echo join('&nbsp;', $items); ?></td>
                    <?php if ( count( $options ) > 1 ): ?>
                    <td>
                        <select name="results">
                        <?php foreach ($options as $option): ?>
                            <?php if ($results == $option): ?>
                            <option value="<?php echo esc_attr( $option ); ?>" selected="selected"><?php echo esc_html( $option ); ?></option>
                            <?php else: ?>
                            <option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </select>
                    </td>
                    <?php endif; ?>
                </tr>
            </tbody>
        </table>
    </form>
</div> -->
<?php if(count($items) > 1){ ?>
<div class="pagination-bottom">
        <ul class="wpv-pagination-nav-links-container js-wpv-pagination-nav-links-container">
            <?php foreach($items as $item){
                if(strpos($item, 'a href')==false){
                    print '<li class="current disabled"><span>'.$item.'</span></li>';
                } else {
                    print '<li>'.$item.'</li>';
                }
            }
            ?>
        </ul>
</div>
<?php } ?>