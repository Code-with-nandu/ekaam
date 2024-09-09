<!-- main Part -->
<div class="container">
    <div class="posts">
    <?php foreach ($asha as $key => $ash) : ?>

<div class="post">
    <div class="thumbnil" loc='<?= $ash['organisation_id']."". $ash['id'] ?> ' ><img class="tn" src="<?= base_url()."assets/image/".$ash['icon_photo'] ?>" alt="<?= $ash['displayname'] ?> campus" style="Width:400px ; height:400px;">
</div>

</div>
<?php endforeach; ?>

    </div>
</div>

<!-- main part -->
