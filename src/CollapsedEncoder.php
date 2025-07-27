<?php
declare(strict_types=1);

namespace Phreakscope;

/**
 * Converts raw profiling data to collapsed stack format
 * 
 * Format: "stack;frame1;frame2 count\n"
 */
class CollapsedEncoder
{
    /**
     * Encode raw profiling data to collapsed format
     * 
     * @param string $rawData Binary data from phreakscope_dump_raw()
     * @return string|false Collapsed format string or false on error
     */
    public function encode(string $rawData): string|false
    {
        if (empty($rawData)) {
            return false;
        }
        
        $offset = 0;
        $len = strlen($rawData);
        
        // Read location dictionary
        if ($offset + 4 > $len) {
            return false;
        }
        
        $locationCount = unpack('V', substr($rawData, $offset, 4))[1];
        $offset += 4;
        
        $locations = [];
        
        for ($i = 0; $i < $locationCount; $i++) {
            if ($offset + 8 > $len) {
                return false;
            }
            
            $id = unpack('V', substr($rawData, $offset, 4))[1];
            $offset += 4;
            
            $keyLen = unpack('V', substr($rawData, $offset, 4))[1];
            $offset += 4;
            
            if ($offset + $keyLen > $len) {
                return false;
            }
            
            $key = substr($rawData, $offset, $keyLen);
            $offset += $keyLen;
            
            $locations[$id] = $key;
        }
        
        // Read samples
        if ($offset + 8 > $len) {
            return false;
        }
        
        $sampleCount = unpack('P', substr($rawData, $offset, 8))[1];
        $offset += 8;
        
        $stacks = [];
        
        for ($i = 0; $i < $sampleCount; $i++) {
            if ($offset + 4 > $len) {
                return false;
            }
            
            $sampleSize = unpack('V', substr($rawData, $offset, 4))[1];
            $offset += 4;
            
            if ($offset + $sampleSize > $len) {
                return false;
            }
            
            $sampleData = substr($rawData, $offset, $sampleSize);
            $offset += $sampleSize;
            
            // Decode VarInt encoded location IDs
            $stack = [];
            $sampleOffset = 0;
            
            while ($sampleOffset < $sampleSize) {
                $value = 0;
                $shift = 0;
                
                while ($sampleOffset < $sampleSize) {
                    $byte = ord($sampleData[$sampleOffset++]);
                    $value |= ($byte & 0x7F) << $shift;
                    
                    if (($byte & 0x80) == 0) {
                        break;
                    }
                    
                    $shift += 7;
                }
                
                if (isset($locations[$value])) {
                    $stack[] = $locations[$value];
                }
            }
            
            // Reverse stack (bottom to top)
            $stack = array_reverse($stack);
            
            // Create stack key
            $stackKey = implode(';', $stack);
            
            if (!isset($stacks[$stackKey])) {
                $stacks[$stackKey] = 0;
            }
            
            $stacks[$stackKey]++;
        }
        
        // Generate collapsed format
        $lines = [];
        
        foreach ($stacks as $stack => $count) {
            if (!empty($stack)) {
                $lines[] = $stack . ' ' . $count;
            }
        }
        
        return implode("\n", $lines) . "\n";
    }
}