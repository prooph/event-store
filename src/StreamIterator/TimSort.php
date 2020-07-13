<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2020 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\StreamIterator;

/**
 * Code is largely lifted from the GeeksforGeeks Java implementation of TimSort.
 *
 * @see https://www.geeksforgeeks.org/timsort/ for the original Java implementation
 */
trait TimSort
{
    /**
     * @var int
     */
    private $timSortRun = 32;

    /**
     * this function sorts array from left index to right index which is of size atmost $timsortRun
     *
     * @param array $arr
     * @param int $left
     * @param int $right
     */
    private function insertionSort(array &$arr, int $left, int $right): void
    {
        for ($i = $left + 1; $i <= $right; $i++) {
            $temp = $arr[$i];
            $j = $i - 1;
            while ($j >= $left && $this->greaterThan($arr[$j][0], $temp[0])) {
                $arr[$j + 1] = $arr[$j];
                $j--;
            }
            $arr[$j + 1] = $temp;
        }
    }

    /**
     * merge function merges the sorted runs
     *
     * @param array $arr
     * @param int $l
     * @param int $m
     * @param int $r
     */
    private function merge(array &$arr, int $l, int $m, int $r): void
    {
        // original array is broken in two parts
        // $left and $right array
        $len1 = $m - $l + 1;
        $len2 = $r - $m;
        $left = [];
        $right = [];
        for ($x = 0; $x < $len1; $x++) {
            $left[$x] = $arr[$l + $x];
        }
        for ($x = 0; $x < $len2; $x++) {
            $right[$x] = $arr[$m + 1 + $x];
        }

        $i = 0;
        $j = 0;
        $k = $l;

        // after comparing, we $merge those two array
        // in $larger sub array
        while ($i < $len1 && $j < $len2) {
            if ($this->lowerThanEqual($left[$i][0], $right[$j][0])) {
                $arr[$k] = $left[$i];
                $i++;
            } else {
                $arr[$k] = $right[$j];
                $j++;
            }
            $k++;
        }

        // copy $remaining elements of $left, if any
        while ($i < $len1) {
            $arr[$k] = $left[$i];
            $k++;
            $i++;
        }

        // copy $remaining element of $right, if any
        while ($j < $len2) {
            $arr[$k] = $right[$j];
            $k++;
            $j++;
        }
    }

    /**
     * Iterative Timsort function to sort the array[0...n-1] (similar to merge sort)
     *
     * @param array $arr
     * @param int $n
     */
    private function timSort(array &$arr, int $n): void
    {
        // Sort individual subarrays of size RUN
        for ($i = 0; $i < $n; $i += $this->timSortRun) {
            $this->insertionSort($arr, $i, \min($i + 31, $n - 1));
        }

        // start merging from size RUN (or 32). It will merge
        // to form size 64, then 128, 256 and so on ....
        for ($size = $this->timSortRun; $size < $n; $size = 2 * $size) {

            // pick starting point of left sub $array. We
            // are going to merge $arr[left..left+size-1]
            // and $arr[left+size, left+2*size-1]
            // After every merge, we increase left by 2*size
            for ($left = 0; $left < $n; $left += 2 * $size) {

                // find ending point of left sub $array
                // mid+1 is starting point of right sub $array
                $mid = $left + $size - 1;
                $right = \min($left + (2 * $size) - 1, $n - 1);

                // This happens when there are an odd number of runs to merge at any given level.
                // the right set would be empty so there is nothing to merge.
                if ($mid >= $n - 1) {
                    continue;
                }
                // merge sub $array $arr[left.....mid] &
                // $arr[mid+1....right]
                $this->merge($arr, $left, $mid, $right);
            }
        }
    }

    private function greaterThan(\Iterator $a, \Iterator $b): bool
    {
        $aValid = $a->valid();
        $bValid = $b->valid();

        if (! $aValid || ! $bValid) {
            return $bValid === true;
        }

        return $a->current()->createdAt() > $b->current()->createdAt();
    }

    private function lowerThanEqual(\Iterator $a, \Iterator $b): bool
    {
        $aValid = $a->valid();
        $bValid = $b->valid();

        if (! $aValid || ! $bValid) {
            return $aValid === true;
        }

        return $a->current()->createdAt() <= $b->current()->createdAt();
    }
}
