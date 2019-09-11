<?php



namespace Illuminate\Support {

    /**
     *
     *
     * @mixin \Illuminate\Support\Collection
     */
    class Collection {
        public function cut(array $names) {
            return \Illuminate\Support\Collection::cut($names);
        }
    }
}
