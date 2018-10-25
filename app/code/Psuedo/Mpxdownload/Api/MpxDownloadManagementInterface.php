<?php
/**
 * Connector to MPX to consume orders
 * Copyright (C) 2017  Our Daily Bread Ministries
 * 
 * This file is part of Psuedo/Mpxdownload.
 * 
 * Psuedo/Mpxdownload is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Psuedo\Mpxdownload\Api;

interface MpxDownloadManagementInterface
{


    /**
     * GET for mpx_download api
     * @param string $param
     * @return string
     */
    public function getMpx_download($param);
}
