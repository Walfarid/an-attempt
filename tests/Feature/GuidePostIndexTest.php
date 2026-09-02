<?php

use Illuminate\Support\Facades\Schema;

test('guide_post table has post_id index', function () {
    expect(Schema::hasIndex('guide_post', 'guide_post_post_id_index'))->toBeTrue();
});
