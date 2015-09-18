        <div id="msg" class="block warning">
            <h2><span><?php _e('Warning') ?></span></h2>
            <div class="box">
                <div class="inbox">
                    <form method="post" action="<?= $feather->request()->getPath(); ?>">
                        <input type="hidden" name="topics" value="<?= implode(",",$topics); ?>" />
                        <input name="delete_comply" value="1" type="hidden" />
                        <input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
                        <div class="inform ">
                            <div class="forminfo">
                                <p><?php _e('Confirm delete', 'private_messages'); ?></p>
                            </div>
                        </div>
                        <p class="buttons"><input type="submit" name="delete" value="<?php _e('Delete'); ?>" /> <a href="javascript:history.go(-1)"><?php _e('Go back') ?></a></p>
                    </form>
                </div>
            </div>
        </div>
        <div class="clearer"></div>
