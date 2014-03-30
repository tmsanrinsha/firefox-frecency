<?php
class GaussianElimination {
    private $_augmentedMatrix;
    private static $_N;

    function __construct($augmentedMatrix) {
        $this->_augmentedMatrix = $augmentedMatrix;
        $this->_N = count($augmentedMatrix);
    }

    public function solve() {
        $this->_forwardEliminate();
        $this->_debug();
        $this->_backSubstitute();
        for ($i = 0; $i < $this->_N; $i++) {
            $answer[] = $this->_augmentedMatrix[$i][$this->_N];
        }
        return $answer;
    }

    /**
     * 前進消去(forward elimination)
     */
    private function _forwardEliminate() {
        for ($i = 0; $i < $this->_N - 1; $i++) {

            $this->_pivot($i);

            $aii = $this->_augmentedMatrix[$i][$i];
            if ($aii == 0) {
                $this->_debug();
                exit;
            }

            for ($row = $i + 1; $row < $this->_N; $row++) {
                $ark = $this->_augmentedMatrix[$row][$i];
                $this->_augmentedMatrix[$row] = array_map(function ($a, $b) use ($ark, $aii) {
                    return $a - $b * $ark / $aii;
                }, $this->_augmentedMatrix[$row], $this->_augmentedMatrix[$i]);
            }
        }
    }

    private function _backSubstitute() {
        for ($i = $this->_N - 1; $i >= 0; $i--) {
            $innerProd = 0;
            for ($j = $i + 1; $j < $this->_N; $j++) {
                $innerProd += $this->_augmentedMatrix[$i][$j] * $this->_augmentedMatrix[$j][$this->_N];
            }

            $this->_augmentedMatrix[$i][$this->_N] = ($this->_augmentedMatrix[$i][$this->_N] -$innerProd) / $this->_augmentedMatrix[$i][$i];
        }
    }

    /**
     * ピボット選択(Pivoting)
     *
     * 行入れ替えだけの部分的ピボッティング
     * $k行目以降で$k列目の要素の絶対が最も大きい行を検索して、$k行目と入れ替える
     *
     * $return void
     */
    private function _pivot($k) {
        $maxRow = $k;
        $max = abs($this->_augmentedMatrix[$k][$k]);
        for ($i = $k + 1; $i < $this->_N; $i++) {
            if (abs($this->_augmentedMatrix[$i][$k]) > $max) {
                $maxRow = $i;
                $max = abs($this->_augmentedMatrix[$i][$k]);
            }
        }

        if ($maxRow != $k) {
            list($this->_augmentedMatrix[$k], $this->_augmentedMatrix[$maxRow]) =
                array($this->_augmentedMatrix[$maxRow], $this->_augmentedMatrix[$k]);
        }
    }

    private function _debug() {
        for ($j = 0; $j < $this->_N; $j++) {
            $len = count($this->_augmentedMatrix[$j]);
            for ($k = 0; $k < $len; $k++) {
                echo $this->_augmentedMatrix[$j][$k] . '	';
            }
            echo PHP_EOL;
        }
    }
}
