<?php

namespace App\Http\Controllers\RJ;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use carbon\Carbon;
use App\Http\Traits\BPJS\AntrianBPJSTrait;

class PasienRawatJalanController extends Controller
{
    use AntrianBPJSTrait;

    public function index(Request $request)
    {

        $search = $request->input('search');

        $myData = $this->queryMyData($search);

        //return view
        return inertia('RJ/PasienRawatJalan', [
            'myData' => $myData,
            'mySearch' => $search
        ]);
    }

    public function queryMyData($search): Collection
    {
        $query = DB::table('rsmst_doctors')
            ->select(
                'dr_id',
                'dr_name',
                'kd_dr_bpjs',
                'dr_uuid',
                'dr_nik',
                'dr_phone',
                'dr_address',
                'rsmst_polis.poli_desc as poli_desc'
            )->join('rsmst_polis', 'rsmst_polis.poli_id', '=',  'rsmst_doctors.poli_id')
            ->where('active_status', '=', '1')
            ->where('dr_name', 'like', '%' . $search . '%')
            ->orderBy('dr_name',  'asc')
            ->get();

        return $query;
    }


    // umum bpjs poli
    ///////////////////////////////////////////////////////
    private function queryPasien($dateRef): Collection
    {
        $myRefstatusId = 'A';
        $myRefdate = $dateRef;

        $query = DB::table('rstxn_rjhdrs')
            ->select(
                DB::raw("to_char(rj_date,'dd/mm/yyyy') AS rj_date"),
                DB::raw("to_char(rj_date,'yyyymmdd') AS rj_date1"),
                'datadaftarpolirj_json'
            )
            // ->where(DB::raw("nvl(erm_status,'A')"), '=', $myRefstatusId)
            ->where('rj_status', '!=', ['A', 'F'])
            ->where('klaim_id', '!=', 'KR')
            ->where(DB::raw("to_char(rj_date,'dd/mm/yyyy')"), '=', $myRefdate)
            ->orderBy('rj_date1',  'desc')
            ->get();

        return $query;
    }

    private function queryPasienBulanan($dateRef): Collection
    {
        $myRefstatusId = 'A';
        $myRefdate = $dateRef;

        $query = DB::table('rstxn_rjhdrs')
            ->select(
                DB::raw("to_char(rj_date,'dd/mm/yyyy') AS rj_date"),
                DB::raw("to_char(rj_date,'yyyymmdd') AS rj_date1"),
                'datadaftarpolirj_json'
            )
            // ->where(DB::raw("nvl(erm_status,'A')"), '=', $myRefstatusId)
            ->where('rj_status', '!=', ['A', 'F'])
            ->where('klaim_id', '!=', 'KR')
            ->where(DB::raw("to_char(rj_date,'mm/yyyy')"), '=', $myRefdate)
            ->orderBy('rj_date1',  'desc')
            ->get();

        return $query;
    }

    public function queryPasienRJUmum($yearRjRef): Collection
    {
        $query = DB::table('rstxn_rjhdrs')->select(
            DB::raw("to_char(rj_date,'mm/yyyy') AS rj_date"),
            DB::raw("to_char(rj_date,'yyyymm') AS rj_date1"),
            DB::raw("count(*) AS jml_kunjungan")
        )->where('rj_status', '!=', ['A', 'F'])
            ->where(DB::raw("to_char(rj_date,'yyyy')"), '=', $yearRjRef)
            ->whereIn('klaim_id', function ($query) {
                $query->select('klaim_id')
                    ->from('rsmst_klaimtypes')
                    ->where('klaim_status', '=', 'UMUM');
            })
            ->groupBy(DB::raw("to_char(rj_date,'mm/yyyy')"))
            ->groupBy(DB::raw("to_char(rj_date,'yyyymm')"))
            ->orderBy('rj_date1',  'asc')
            ->get();
        return $query;
    }

    public function queryPasienRJBpjs($yearRjRef): Collection
    {
        $query = DB::table('rstxn_rjhdrs')
            ->select(
                DB::raw("to_char(rj_date,'mm/yyyy') AS rj_date"),
                DB::raw("to_char(rj_date,'yyyymm') AS rj_date1"),
                DB::raw("count(*) AS jml_kunjungan"),

            )
            ->where('rj_status', '!=', ['A', 'F'])
            ->where(DB::raw("to_char(rj_date,'yyyy')"), '=', $yearRjRef)
            ->whereIn('klaim_id', function ($query) {
                $query->select('klaim_id')
                    ->from('rsmst_klaimtypes')
                    ->where('klaim_status', '=', 'BPJS');
            })
            ->groupBy(DB::raw("to_char(rj_date,'mm/yyyy')"))
            ->groupBy(DB::raw("to_char(rj_date,'yyyymm')"))
            ->orderBy('rj_date1',  'asc')
            ->get();
        return $query;
    }

    public function queryPasienRJKronis($yearRjRef): Collection
    {
        $query = DB::table('rstxn_rjhdrs')
            ->select(
                DB::raw("to_char(rj_date,'mm/yyyy') AS rj_date"),
                DB::raw("to_char(rj_date,'yyyymm') AS rj_date1"),
                DB::raw("count(*) AS jml_kunjungan"),

            )
            ->where('rj_status', '!=', ['A', 'F'])
            ->where(DB::raw("to_char(rj_date,'yyyy')"), '=', $yearRjRef)
            ->whereIn('klaim_id', function ($query) {
                $query->select('klaim_id')
                    ->from('rsmst_klaimtypes')
                    ->where('klaim_status', '=', 'KRONIS');
            })
            ->groupBy(DB::raw("to_char(rj_date,'mm/yyyy')"))
            ->groupBy(DB::raw("to_char(rj_date,'yyyymm')"))
            ->orderBy('rj_date1',  'asc')
            ->get();
        return $query;
    }

    // umum bpjs poli per poli
    ///////////////////////////////////////////////////////
    public function indexRJPoli(Request $request)
    {

        $yearRjRef = $request->input('yearRef') ? $request->input('yearRef') : Carbon::now()->format('Y');
        $poliId = $request->input('kodePoli') ? $request->input('kodePoli') : 1;

        $poli = $this->displayPoli($poliId);
        $queryPasienRJUmumPoli = $this->queryPasienRJUmumPoli($yearRjRef, $poliId);
        $queryPasienRJBpjsPoli = $this->queryPasienRJBpjsPoli($yearRjRef, $poliId);
        $queryPasienRJKronisPoli = $this->queryPasienRJKronisPoli($yearRjRef, $poliId);

        //return view
        return inertia('RJ/PasienRawatJalanPoli', [
            'poli' => $poli,
            'yearRjRef' => $yearRjRef,
            'queryPasienRJUmumPoli' => $queryPasienRJUmumPoli,
            'queryPasienRJBpjsPoli' => $queryPasienRJBpjsPoli,
            'queryPasienRJKronisPoli' => $queryPasienRJKronisPoli,

        ]);
    }

    public function displayPoli($poliId = 1): array
    {
        $query = json_decode(json_encode(DB::table('rsmst_polis')
            ->select('poli_id', 'poli_desc')
            ->where('poli_id', '=', $poliId)
            ->first(), true), true);
        return $query;
    }
    private function queryPasienRJUmumPoli($yearRjRef, $poliId = 1): Collection
    {
        $query = DB::table('rstxn_rjhdrs')
            ->select(
                DB::raw("to_char(rj_date,'mm/yyyy') AS rj_date"),
                DB::raw("to_char(rj_date,'yyyymm') AS rj_date1"),
                DB::raw("count(*) AS jml_kunjungan"),

            )
            ->where('rj_status', '!=', ['A', 'F'])
            ->where(DB::raw("to_char(rj_date,'yyyy')"), '=', $yearRjRef)
            ->where('poli_id', '=', $poliId)
            ->whereIn('klaim_id', function ($query) {
                $query->select('klaim_id')
                    ->from('rsmst_klaimtypes')
                    ->where('klaim_status', '=', 'UMUM');
            })
            ->groupBy(DB::raw("to_char(rj_date,'mm/yyyy')"))
            ->groupBy(DB::raw("to_char(rj_date,'yyyymm')"))
            ->groupBy('poli_id')

            ->orderBy('rj_date1',  'asc')
            ->get();
        return $query;
    }

    private function queryPasienRJBpjsPoli($yearRjRef, $poliId = 1): Collection
    {
        $query = DB::table('rstxn_rjhdrs')
            ->select(
                DB::raw("to_char(rj_date,'mm/yyyy') AS rj_date"),
                DB::raw("to_char(rj_date,'yyyymm') AS rj_date1"),
                DB::raw("count(*) AS jml_kunjungan"),

            )
            ->where('rj_status', '!=', ['A', 'F'])
            ->where(DB::raw("to_char(rj_date,'yyyy')"), '=', $yearRjRef)
            ->where('poli_id', '=', $poliId)
            ->whereIn('klaim_id', function ($query) {
                $query->select('klaim_id')
                    ->from('rsmst_klaimtypes')
                    ->where('klaim_status', '=', 'BPJS');
            })
            ->groupBy(DB::raw("to_char(rj_date,'mm/yyyy')"))
            ->groupBy(DB::raw("to_char(rj_date,'yyyymm')"))
            ->orderBy('rj_date1',  'asc')
            ->get();
        return $query;
    }

    private function queryPasienRJKronisPoli($yearRjRef, $poliId = 1): Collection
    {
        $query = DB::table('rstxn_rjhdrs')
            ->select(
                DB::raw("to_char(rj_date,'mm/yyyy') AS rj_date"),
                DB::raw("to_char(rj_date,'yyyymm') AS rj_date1"),
                DB::raw("count(*) AS jml_kunjungan"),

            )
            ->where('rj_status', '!=', ['A', 'F'])
            ->where(DB::raw("to_char(rj_date,'yyyy')"), '=', $yearRjRef)
            ->where('poli_id', '=', $poliId)
            ->whereIn('klaim_id', function ($query) {
                $query->select('klaim_id')
                    ->from('rsmst_klaimtypes')
                    ->where('klaim_status', '=', 'KRONIS');
            })
            ->groupBy(DB::raw("to_char(rj_date,'mm/yyyy')"))
            ->groupBy(DB::raw("to_char(rj_date,'yyyymm')"))
            ->orderBy('rj_date1',  'asc')
            ->get();
        return $query;
    }

    // EMR RJ
    ///////////////////////////////////////////////////////
    public function indexEMRRJ(Request $request)
    {
        $date = $request->input('date') ? $request->input('date') : Carbon::now()->format('d/m/Y');
        $page = $request->input('page') ? $request->input('page') : 1;
        $show = $request->input('show') ? $request->input('show') : 10;



        $queryPasienEMRRJ = $this->queryPasienEmrRJ($date, $show);
        $queryPasienEmrRJKelengkapanPengisianHarian = $this->queryPasienEmrRJKelengkapanPengisianHarian($date);

        //return view
        return inertia('RJ/PasienEMRRawatJalan', [
            'date' => $date,
            'page' => $page,
            'show' => $show,
            'queryPasienEMRRJ' => $queryPasienEMRRJ,
            'queryPasienEmrRJKelengkapanPengisianHarian' => $queryPasienEmrRJKelengkapanPengisianHarian
        ]);
    }



    private function queryPasienEmrRJ($dateRef, $show = 10)
    {
        $myRefstatusId = 'A';
        $myRefdate = $dateRef;

        $query = DB::table('rsview_rjkasir')
            ->select(
                DB::raw("to_char(rj_date,'dd/mm/yyyy hh24:mi:ss') AS rj_date"),
                DB::raw("to_char(rj_date,'yyyymmddhh24miss') AS rj_date1"),
                'rj_no',
                'reg_no',
                'reg_name',
                'sex',
                'address',
                'thn',
                DB::raw("to_char(birth_date,'dd/mm/yyyy') AS birth_date"),
                'poli_id',
                'poli_desc',
                'dr_id',
                'dr_name',
                'klaim_id',
                'shift',
                'vno_sep',
                'no_antrian',
                'rj_status',
                'nobooking',
                'push_antrian_bpjs_status',
                'push_antrian_bpjs_json',
                'datadaftarpolirj_json'
            )
            // ->where(DB::raw("nvl(erm_status,'A')"), '=', $myRefstatusId)
            ->where('rj_status', '!=', ['A', 'F'])
            ->where('klaim_id', '!=', 'KR')
            ->where(DB::raw("to_char(rj_date,'dd/mm/yyyy')"), '=', $myRefdate)
            ->orderBy('rj_date1',  'desc')
            ->orderBy('shift',  'asc')
            ->orderBy('no_antrian',  'desc')
            ->orderBy('dr_name',  'asc')
            ->paginate($show);

        return $query;
    }

    private function queryPasienEmrRJBulanan($dateRef, $show = 10)
    {
        $myRefstatusId = 'A';
        $myRefdate = $dateRef;

        $query = DB::table('rsview_rjkasir')
            ->select(
                DB::raw("to_char(rj_date,'dd/mm/yyyy hh24:mi:ss') AS rj_date"),
                DB::raw("to_char(rj_date,'yyyymmddhh24miss') AS rj_date1"),
                'rj_no',
                'reg_no',
                'reg_name',
                'sex',
                'address',
                'thn',
                DB::raw("to_char(birth_date,'dd/mm/yyyy') AS birth_date"),
                'poli_id',
                'poli_desc',
                'dr_id',
                'dr_name',
                'klaim_id',
                'shift',
                'vno_sep',
                'no_antrian',
                'rj_status',
                'nobooking',
                'push_antrian_bpjs_status',
                'push_antrian_bpjs_json',
                'datadaftarpolirj_json'
            )
            // ->where(DB::raw("nvl(erm_status,'A')"), '=', $myRefstatusId)
            ->where('rj_status', '!=', ['A', 'F'])
            ->where('klaim_id', '!=', 'KR')
            ->where(DB::raw("to_char(rj_date,'mm/yyyy')"), '=', $myRefdate)
            ->orderBy('rj_date1',  'desc')
            ->orderBy('shift',  'asc')
            ->orderBy('no_antrian',  'desc')
            ->orderBy('dr_name',  'asc')
            ->paginate($show);

        return $query;
    }

    private function queryPasienEmrRJKelengkapanPengisianHarian($dateRef): array
    {
        //total lengkap
        ////////////////////////////////////////////////
        $queryTotal = $this->queryPasien($dateRef);

        //    cari berdasarkan JSON Table
        // emr
        $queryLengkap = $queryTotal->filter(function ($item) {
            $datadaftarpolirj_json = json_decode($item->datadaftarpolirj_json, true);
            $anamnesa = isset($datadaftarpolirj_json['anamnesa']) ? 1 : 0;
            $pemeriksaan = isset($datadaftarpolirj_json['pemeriksaan']) ? 1 : 0;
            $penilaian = isset($datadaftarpolirj_json['penilaian']) ? 1 : 0;
            $procedure = isset($datadaftarpolirj_json['procedure']) ? 1 : 0;
            $diagnosis = isset($datadaftarpolirj_json['diagnosis']) ? 1 : 0;
            $perencanaan = isset($datadaftarpolirj_json['perencanaan']) ? 1 : 0;
            $prosentaseEMR =
                (($anamnesa + $pemeriksaan + $penilaian + $procedure + $diagnosis + $perencanaan) / 6) *
                100;

            if ($prosentaseEMR >= 80) {
                return 'x';
            }
        })->count();

        // DiagnosisIcd
        $queryDiagnosisIcd = $queryTotal->filter(function ($item) {
            $datadaftarpolirj_json = json_decode($item->datadaftarpolirj_json, true);
            $diagnosis = isset($datadaftarpolirj_json['diagnosis']) ? count($datadaftarpolirj_json['diagnosis']) : 0;
            if ($diagnosis > 0) {
                return 'x';
            }
        })->count();

        // SatuSehat
        $querySatuSehat = $queryTotal->filter(function ($item) {
            $datadaftarpolirj_json = json_decode($item->datadaftarpolirj_json, true);
            $satuSehatUuidRJ = isset($datadaftarpolirj_json['satuSehatUuidRJ']) ? count($datadaftarpolirj_json['satuSehatUuidRJ']) : 0;
            if ($satuSehatUuidRJ > 0) {
                return 'x';
            }
        })->count();

        $query = [
            'queryTotal' => $queryTotal->count(),
            'queryLengkap' => $queryLengkap,
            'queryDiagnosisIcd' => $queryDiagnosisIcd,
            'querySatuSehat' => $querySatuSehat
        ];
        return $query;
    }


    // MJKN RJ
    ///////////////////////////////////////////////////////
    public function indexBookingMjkn(Request $request)
    {
        $month = $request->input('date') ? $request->input('date') : Carbon::now()->format('m/Y');
        $show = $request->input('show') ? $request->input('show') : 10;





        $queryBookingMjkn = $this->queryBookingMjkn($month);
        $queryBookingMjknCheckin = $this->queryBookingMjknCheckin($month);
        $queryBookingMjknBelum = $this->queryBookingMjknBelum($month);
        $queryBookingMjknBatal = $this->queryBookingMjknBatal($month);


        $queryDataBookingMjkn = $this->queryDataBookingMjkn($month, $show);

        //return view
        return inertia('RJ/BookingMJKN', [
            'date' => $month,
            'show' => $show,
            'queryBookingMjkn' => $queryBookingMjkn,
            'queryBookingMjknCheckin' => $queryBookingMjknCheckin,
            'queryBookingMjknBelum' => $queryBookingMjknBelum,
            'queryBookingMjknBatal' => $queryBookingMjknBatal,
            'queryDataBookingMjkn' => $queryDataBookingMjkn

        ]);
    }

    private function queryBookingMjkn($monthRef): Collection
    {

        $query = DB::table('referensi_mobilejkn_bpjs')->select(
            DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'dd/mm/yyyy') AS tanggalperiksa"),
            DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'yyyymmdd') AS tanggalperiksa1"),
            DB::raw("count(*) AS jml_kunjungan")
        )
            ->where(DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'mm/yyyy')"), '=', $monthRef)

            ->groupBy(DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'dd/mm/yyyy')"))
            ->groupBy(DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'yyyymmdd')"))
            ->orderBy('tanggalperiksa1',  'asc')
            ->get();


        return $query;
    }

    private function queryBookingMjknCheckin($monthRef): Collection
    {
        $query = DB::table('referensi_mobilejkn_bpjs')->select(
            DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'dd/mm/yyyy') AS tanggalperiksa"),
            DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'yyyymmdd') AS tanggalperiksa1"),
            DB::raw("count(*) AS jml_kunjungan")
        )
            ->where(DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'mm/yyyy')"), '=', $monthRef)
            ->where('status', '=', 'Checkin')
            ->groupBy(DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'dd/mm/yyyy')"))
            ->groupBy(DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'yyyymmdd')"))
            ->orderBy('tanggalperiksa1',  'asc')
            ->get();
        return $query;
    }

    private function queryBookingMjknBelum($monthRef): Collection
    {
        $query = DB::table('referensi_mobilejkn_bpjs')->select(
            DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'dd/mm/yyyy') AS tanggalperiksa"),
            DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'yyyymmdd') AS tanggalperiksa1"),
            DB::raw("count(*) AS jml_kunjungan")
        )
            ->where(DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'mm/yyyy')"), '=', $monthRef)
            ->where('status', '=', 'Belum')
            ->groupBy(DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'dd/mm/yyyy')"))
            ->groupBy(DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'yyyymmdd')"))
            ->orderBy('tanggalperiksa1',  'asc')
            ->get();
        return $query;
    }

    private function queryBookingMjknBatal($monthRef): Collection
    {
        $query = DB::table('referensi_mobilejkn_bpjs')->select(
            DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'dd/mm/yyyy') AS tanggalperiksa"),
            DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'yyyymmdd') AS tanggalperiksa1"),
            DB::raw("count(*) AS jml_kunjungan")
        )
            ->where(DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'mm/yyyy')"), '=', $monthRef)
            ->where('status', '=', 'Batal')
            ->groupBy(DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'dd/mm/yyyy')"))
            ->groupBy(DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'yyyymmdd')"))
            ->orderBy('tanggalperiksa1',  'asc')
            ->get();
        return $query;
    }

    private function queryDataBookingMjkn($monthRef, $show = 10)
    {

        $query = DB::table('referensi_mobilejkn_bpjs')
            ->select(
                'nobooking',
                'no_rawat',
                'nomorkartu',
                'nik',
                'nohp',
                'kodepoli',
                DB::raw("(select poli_desc from rsmst_polis where kd_poli_bpjs=referensi_mobilejkn_bpjs.kodepoli)poli_desc"),
                'pasienbaru',
                'norm',
                'kodedokter',
                DB::raw("(select dr_name from rsmst_doctors where kd_dr_bpjs=referensi_mobilejkn_bpjs.kodedokter and rownum = 1)dr_name "),
                DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'dd/mm/yyyy') as tanggalperiksa"),
                'jampraktek',
                'jeniskunjungan',
                'nomorreferensi',
                'nomorantrean',
                'angkaantrean',
                'estimasidilayani',
                'sisakuotajkn',
                'kuotajkn',
                'sisakuotanonjkn',
                'kuotanonjkn',
                'status',
                'validasi',
                'statuskirim',
                'keterangan_batal',
                'tanggalbooking',
                'daftardariapp',
                'reg_name',
                'address',
                DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'yyyymmdd') AS tanggalperiksa1"),
            )
            ->join('rsmst_pasiens', 'referensi_mobilejkn_bpjs.norm', 'rsmst_pasiens.reg_no')
            ->where(DB::raw("to_char(to_date(tanggalperiksa,'yyyy-mm-dd'),'mm/yyyy')"), '=', $monthRef)
            ->orderBy('tanggalperiksa1',  'asc')
            ->paginate($show);

        return $query;
    }


    // TaskId RJ
    ///////////////////////////////////////////////////////
    public function indexTaskIdRJ(Request $request)
    {
        $date = $request->input('date') ? $request->input('date') : Carbon::now()->format('d/m/Y');
        $page = $request->input('page') ? $request->input('page') : 1;
        $show = $request->input('show') ? $request->input('show') : 10;
        $find = $request->input('find') ? $request->input('find') : '';


        $queryPasienEMRRJ = $this->queryPasienEmrRJ($date, $show);
        $getlistTaskIdAntrianLengkap = $this->getlistTaskIdAntrianLengkap($date);
        $getRataWaktuLayananPoli = $this->getRataWaktuLayananPoli($date);
        $getRataWaktuLayananApotek = $this->getRataWaktuLayananApotek($date);

        foreach ($queryPasienEMRRJ as $key => $item) {
            $getlisttask = json_decode($this->getlisttask($item->nobooking)->getContent(), true);
            foreach ($getlisttask['response'] as $task) {
                // dd($task);
                if (isset($task['taskid'])) {
                    switch ($task) {
                        case $task['taskid'] == 1:
                            $queryPasienEMRRJ[$key]->taskIdBPJSStatusKirimTaskId1 = $task['wakturs'];
                            break;
                        case $task['taskid'] == 2:
                            $queryPasienEMRRJ[$key]->taskIdBPJSStatusKirimTaskId2 = $task['wakturs'];
                            break;
                        case $task['taskid'] == 3:
                            $queryPasienEMRRJ[$key]->taskIdBPJSStatusKirimTaskId3 = $task['wakturs'];
                            break;
                        case $task['taskid'] == 4:
                            $queryPasienEMRRJ[$key]->taskIdBPJSStatusKirimTaskId4 = $task['wakturs'];
                            break;
                        case $task['taskid'] == 5:
                            $queryPasienEMRRJ[$key]->taskIdBPJSStatusKirimTaskId5 = $task['wakturs'];
                            break;
                        case $task['taskid'] == 6:
                            $queryPasienEMRRJ[$key]->taskIdBPJSStatusKirimTaskId6 = $task['wakturs'];
                            break;
                        case $task['taskid'] == 7:
                            $queryPasienEMRRJ[$key]->taskIdBPJSStatusKirimTaskId7 = $task['wakturs'];
                            break;
                    }
                }
            }
        }



        //return view
        return inertia('RJ/PasienTaskIdRawatJalan', [
            'date' => $date,
            'page' => $page,
            'show' => $show,
            'queryPasienEMRRJ' => $queryPasienEMRRJ,
            'getlistTaskIdAntrianLengkap' => $getlistTaskIdAntrianLengkap,
            'getRataWaktuLayananPoli' => $getRataWaktuLayananPoli,
            'getRataWaktuLayananApotek' => $getRataWaktuLayananApotek
        ]);
    }


    public function indexTaskIdRJBulanan(Request $request)
    {
        $date = $request->input('date') ? $request->input('date') : Carbon::now()->format('m/Y');
        $page = $request->input('page') ? $request->input('page') : 1;
        $show = $request->input('show') ? $request->input('show') : 100;
        $find = $request->input('find') ? $request->input('find') : '';


        $queryPasienEMRRJBulanan = $this->queryPasienEmrRJBulanan($date, $show);
        $getlistTaskIdAntrianLengkapBulanan = $this->getlistTaskIdAntrianLengkapBulanan($date);
        $getRataWaktuLayananPoliBulanan = $this->getRataWaktuLayananPoliBulanan($date);
        $getRataWaktuLayananApotekBulanan = $this->getRataWaktuLayananApotekBulanan($date);

        // foreach ($queryPasienEMRRJBulanan as $key => $item) {
        //     $getlisttask = json_decode($this->getlisttask($item->nobooking)->getContent(), true);
        //     foreach ($getlisttask['response'] as $task) {
        //         // dd($task);
        //         if (isset($task['taskid'])) {
        //             switch ($task) {
        //                 case $task['taskid'] == 1:
        //                     $queryPasienEMRRJBulanan[$key]->taskIdBPJSStatusKirimTaskId1 = $task['wakturs'];
        //                     break;
        //                 case $task['taskid'] == 2:
        //                     $queryPasienEMRRJBulanan[$key]->taskIdBPJSStatusKirimTaskId2 = $task['wakturs'];
        //                     break;
        //                 case $task['taskid'] == 3:
        //                     $queryPasienEMRRJBulanan[$key]->taskIdBPJSStatusKirimTaskId3 = $task['wakturs'];
        //                     break;
        //                 case $task['taskid'] == 4:
        //                     $queryPasienEMRRJBulanan[$key]->taskIdBPJSStatusKirimTaskId4 = $task['wakturs'];
        //                     break;
        //                 case $task['taskid'] == 5:
        //                     $queryPasienEMRRJBulanan[$key]->taskIdBPJSStatusKirimTaskId5 = $task['wakturs'];
        //                     break;
        //                 case $task['taskid'] == 6:
        //                     $queryPasienEMRRJBulanan[$key]->taskIdBPJSStatusKirimTaskId6 = $task['wakturs'];
        //                     break;
        //                 case $task['taskid'] == 7:
        //                     $queryPasienEMRRJBulanan[$key]->taskIdBPJSStatusKirimTaskId7 = $task['wakturs'];
        //                     break;
        //             }
        //         }
        //     }
        // }

        $datadaftarpolirj = [];
        foreach ($queryPasienEMRRJBulanan as $key => $item) {
            $datadaftarpolirj[$key] = json_decode($item->datadaftarpolirj_json, true);
        }

        //return view
        return inertia('RJ/PasienTaskIdRawatJalanBulanan', [
            'date' => $date,
            'page' => $page,
            'show' => $show,
            'queryPasienEMRRJBulanan' => $queryPasienEMRRJBulanan,
            'getlistTaskIdAntrianLengkapBulanan' => $getlistTaskIdAntrianLengkapBulanan,
            'getRataWaktuLayananPoliBulanan' => $getRataWaktuLayananPoliBulanan,
            'getRataWaktuLayananApotekBulanan' => $getRataWaktuLayananApotekBulanan,
        ]);
    }

    public function indexTaskIdRJBulananPerDokter(Request $request)
    {
        $date = $request->input('date') ? $request->input('date') : Carbon::now()->format('m/Y');
        $page = $request->input('page') ? $request->input('page') : 1;
        $show = $request->input('show') ? $request->input('show') : 10;
        $find = $request->input('find') ? $request->input('find') : '';


        $queryPasienEMRRJBulananPerDokter = DB::table('rsview_rjkasir')
            ->select(
                'datadaftarpolirj_json'
            )
            // ->where(DB::raw("nvl(erm_status,'A')"), '=', $myRefstatusId)
            ->where('rj_status', '!=', ['A', 'F'])
            ->where('klaim_id', '!=', 'KR')
            ->where(DB::raw("to_char(rj_date,'mm/yyyy')"), '=', $date)
            ->orderBy('rj_no', 'asc')
            ->get();


        $dataDaftarPoliRJ = [];
        foreach ($queryPasienEMRRJBulananPerDokter as $key => $item) {
            $dataDaftarPoliRJ[$key] = json_decode($item->datadaftarpolirj_json ?? '{}', true);
        }
        // (2) BUAT PENAMPUNG PER DOKTER
        $rekapDokter = [];  // misal: [ 'dr. A' => [ ... ], 'dr. B' => [ ... ] ]

        // (4) LOOPING TIAP PASIEN
        foreach ($dataDaftarPoliRJ as $pasien) {
            $drDesc = $pasien['drDesc'];

            // Jika belum ada di rekap, inisialisasi
            if (!isset($rekapDokter[$drDesc])) {
                $rekapDokter[$drDesc] = [
                    'jumlahPasien' => 0,
                    // total wait times
                    'total_admisi' => 0,   // (3->4)
                    'count_admisi' => 0,
                    'total_poli' => 0,     // (4->5)
                    'count_poli' => 0,
                    'total_apotek' => 0,   // (6->7)
                    'count_apotek' => 0,
                    'total_rajal' => 0,   // (6->7)
                    'count_rajal' => 0,
                ];
            }
            // Tambah jumlah pasien
            $rekapDokter[$drDesc]['jumlahPasien']++;

            // Ambil tasks
            $tasks = $pasien['taskIdPelayanan'];

            // Cari data task3,4,5,6,7
            $t3 = $tasks['taskId3'] ?? null;
            $t4 = $tasks['taskId4'] ?? null;
            $t5 = $tasks['taskId5'] ?? null;
            $t6 = $tasks['taskId6'] ?? null;
            $t7 = $tasks['taskId7'] ?? null;

            // (A) Waktu Tunggu Admisi = selisih (task3 -> task4)
            if ($t3 && $t4 && isset($t3) && isset($t4)) {
                $wait_admisi = Carbon::createFromFormat('d/m/Y H:i:s', $t3)->diffInMinutes(Carbon::createFromFormat('d/m/Y H:i:s', $t4));

                $rekapDokter[$drDesc]['total_admisi'] += $wait_admisi;
                $rekapDokter[$drDesc]['count_admisi']++;
            }

            // (B) Waktu Tunggu Poli = selisih (task4 -> task5)
            if ($t4 && $t5 && isset($t4) && isset($t5)) {
                $wait_poli = Carbon::createFromFormat('d/m/Y H:i:s', $t4)->diffInMinutes(Carbon::createFromFormat('d/m/Y H:i:s', $t5));

                $rekapDokter[$drDesc]['total_poli'] += $wait_poli;
                $rekapDokter[$drDesc]['count_poli']++;
            }

            // (C) Waktu Tunggu Apotek = selisih (task6 -> task7)
            //   (jika Anda ingin menambahkan task5->6 juga, silakan modifikasi)
            if ($t6 && $t7 && isset($t6) && isset($t7)) {
                $wait_apotek = Carbon::createFromFormat('d/m/Y H:i:s', $t6)->diffInMinutes(Carbon::createFromFormat('d/m/Y H:i:s', $t7));
                $rekapDokter[$drDesc]['total_apotek'] += $wait_apotek;
                $rekapDokter[$drDesc]['count_apotek']++;
            }

            if ($t3 && $t7 && isset($t3) && isset($t7)) {
                $wait_rajal = Carbon::createFromFormat('d/m/Y H:i:s', $t3)->diffInMinutes(Carbon::createFromFormat('d/m/Y H:i:s', $t7));
                $rekapDokter[$drDesc]['total_rajal'] += $wait_rajal;
                $rekapDokter[$drDesc]['count_rajal']++;
            }
        }

        // (5) HITUNG RATA-RATA / SIAPKAN OUTPUT
        $hasil = [];
        foreach ($rekapDokter as $dokter => $val) {
            // Rata-rata
            $avg_admisi = ($val['count_admisi'] > 0)
                ? $val['total_admisi'] / $val['count_admisi']
                : 0;
            $avg_poli = ($val['count_poli'] > 0)
                ? $val['total_poli'] / $val['count_poli']
                : 0;
            $avg_apotek = ($val['count_apotek'] > 0)
                ? $val['total_apotek'] / $val['count_apotek']
                : 0;

            $avg_rajal = ($val['count_rajal'] > 0)
                ? $val['total_rajal'] / $val['count_rajal']
                : 0;

            $hasil[] = [
                'nama_dokter'        => $dokter,
                'jumlah_pasien'      => $val['jumlahPasien'],
                'waktu_tunggu_admisi' => round($avg_admisi, 2),  // dibulatkan 2 desimal
                'waktu_tunggu_poli'  => round($avg_poli, 2),
                'waktu_tunggu_apotek' => round($avg_apotek, 2),
                'waktu_tunggu_rajal' => round($avg_rajal, 2),
            ];
        }

        //return view
        return inertia('RJ/PasienTaskIdRawatJalanBulananPerDokter', [
            'date' => $date,
            'page' => $page,
            'show' => $show,
            'queryHasilPerDokter' => $hasil,
            'queryPasienEMRRJBulananPerDokter' => $queryPasienEMRRJBulananPerDokter,
        ]);
    }

    private function getlistTaskIdAntrianLengkap($dateRef): int
    {
        $queryTotal = $this->queryPasien($dateRef);

        //    cari berdasarkan JSON Table
        // emr
        $queryTaskIdAntrianLengkap = $queryTotal->filter(function ($item) {
            $datadaftarpolirj_json = json_decode($item->datadaftarpolirj_json, true);

            if (
                isset($datadaftarpolirj_json['taskIdPelayanan']['taskId1']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId1'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId2']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId2'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId3']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId3'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId4']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId4'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId5']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId5'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId6']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId6'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId7']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId7'])
            ) {
                return 'x';
            } else if (
                isset($datadaftarpolirj_json['taskIdPelayanan']['taskId1']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId1'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId2']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId2'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId3']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId3'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId4']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId4'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId5']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId5'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId6']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId6'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId7']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId7'])
            ) {
                return 'x';
            } else if (
                isset($datadaftarpolirj_json['taskIdPelayanan']['taskId1']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId1'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId2']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId2'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId3']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId3'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId4']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId4'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId5']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId5'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId6']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId6'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId7']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId7'])
            ) {
                return 'x';
            }
        })->count();

        return $queryTaskIdAntrianLengkap;
    }

    private function getlistTaskIdAntrianLengkapBulanan($dateRef): int
    {
        $queryTotal = $this->queryPasienBulanan($dateRef);

        //    cari berdasarkan JSON Table
        // emr
        $queryTaskIdAntrianLengkap = $queryTotal->filter(function ($item) {
            $datadaftarpolirj_json = json_decode($item->datadaftarpolirj_json, true);

            if (
                isset($datadaftarpolirj_json['taskIdPelayanan']['taskId1']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId1'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId2']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId2'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId3']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId3'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId4']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId4'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId5']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId5'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId6']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId6'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId7']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId7'])
            ) {
                return 'x';
            } else if (
                isset($datadaftarpolirj_json['taskIdPelayanan']['taskId1']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId1'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId2']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId2'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId3']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId3'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId4']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId4'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId5']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId5'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId6']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId6'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId7']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId7'])
            ) {
                return 'x';
            } else if (
                isset($datadaftarpolirj_json['taskIdPelayanan']['taskId1']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId1'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId2']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId2'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId3']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId3'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId4']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId4'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId5']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId5'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId6']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId6'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId7']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId7'])
            ) {
                return 'x';
            }
        })->count();

        return $queryTaskIdAntrianLengkap;
    }

    private function getRataWaktuLayananPoli($dateRef): int
    {
        $queryTotal = $this->queryPasien($dateRef);

        //    cari berdasarkan JSON Table
        // emr
        $waktuLayananPoli = $queryTotal->avg(function ($item) {
            $datadaftarpolirj_json = json_decode($item->datadaftarpolirj_json, true);

            if (
                isset($datadaftarpolirj_json['taskIdPelayanan']['taskId1']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId1'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId2']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId2'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId3']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId3'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId4']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId4'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId5']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId5'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId6']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId6'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId7']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId7'])
            ) {
                $startTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId3'], 'Asia/Jakarta');
                $endTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId4'], 'Asia/Jakarta');
                return $endTime->diffInMinutes($startTime);
            } else if (
                isset($datadaftarpolirj_json['taskIdPelayanan']['taskId1']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId1'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId2']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId2'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId3']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId3'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId4']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId4'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId5']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId5'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId6']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId6'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId7']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId7'])
            ) {
                $startTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId3'], 'Asia/Jakarta');
                $endTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId4'], 'Asia/Jakarta');
                return $endTime->diffInMinutes($startTime);
            } else if (
                isset($datadaftarpolirj_json['taskIdPelayanan']['taskId1']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId1'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId2']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId2'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId3']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId3'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId4']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId4'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId5']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId5'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId6']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId6'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId7']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId7'])
            ) {
                $startTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId3'], 'Asia/Jakarta');
                $endTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId4'], 'Asia/Jakarta');
                return $endTime->diffInMinutes($startTime);
            }
        });

        return $waktuLayananPoli ?? 0;
    }

    private function getRataWaktuLayananPoliBulanan($dateRef): int
    {
        $queryTotal = $this->queryPasienBulanan($dateRef);

        //    cari berdasarkan JSON Table
        // emr
        $waktuLayananPoli = $queryTotal->avg(function ($item) {
            $datadaftarpolirj_json = json_decode($item->datadaftarpolirj_json, true);

            if (
                isset($datadaftarpolirj_json['taskIdPelayanan']['taskId1']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId1'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId2']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId2'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId3']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId3'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId4']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId4'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId5']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId5'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId6']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId6'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId7']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId7'])
            ) {
                $startTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId3'], 'Asia/Jakarta');
                $endTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId4'], 'Asia/Jakarta');
                return $endTime->diffInMinutes($startTime);
            } else if (
                isset($datadaftarpolirj_json['taskIdPelayanan']['taskId1']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId1'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId2']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId2'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId3']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId3'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId4']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId4'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId5']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId5'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId6']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId6'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId7']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId7'])
            ) {
                $startTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId3'], 'Asia/Jakarta');
                $endTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId4'], 'Asia/Jakarta');
                return $endTime->diffInMinutes($startTime);
            } else if (
                isset($datadaftarpolirj_json['taskIdPelayanan']['taskId1']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId1'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId2']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId2'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId3']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId3'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId4']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId4'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId5']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId5'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId6']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId6'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId7']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId7'])
            ) {
                $startTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId3'], 'Asia/Jakarta');
                $endTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId4'], 'Asia/Jakarta');
                return $endTime->diffInMinutes($startTime);
            }
        });

        return $waktuLayananPoli ?? 0;
    }

    private function getRataWaktuLayananApotek($dateRef): int
    {
        $queryTotal = $this->queryPasien($dateRef);

        //    cari berdasarkan JSON Table
        // emr
        $waktuLayananPoli = $queryTotal->avg(function ($item) {
            $datadaftarpolirj_json = json_decode($item->datadaftarpolirj_json, true);

            if (
                isset($datadaftarpolirj_json['taskIdPelayanan']['taskId1']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId1'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId2']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId2'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId3']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId3'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId4']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId4'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId5']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId5'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId6']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId6'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId7']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId7'])
            ) {
                $startTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId6'], 'Asia/Jakarta');
                $endTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId7'], 'Asia/Jakarta');
                return $endTime->diffInMinutes($startTime);
            } else if (
                isset($datadaftarpolirj_json['taskIdPelayanan']['taskId1']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId1'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId2']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId2'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId3']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId3'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId4']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId4'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId5']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId5'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId6']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId6'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId7']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId7'])
            ) {
                $startTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId6'], 'Asia/Jakarta');
                $endTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId7'], 'Asia/Jakarta');
                return $endTime->diffInMinutes($startTime);
            }
        });

        return $waktuLayananPoli ?? 0;
    }

    private function getRataWaktuLayananApotekBulanan($dateRef): int
    {
        $queryTotal = $this->queryPasienBulanan($dateRef);

        //    cari berdasarkan JSON Table
        // emr
        $waktuLayananPoli = $queryTotal->avg(function ($item) {
            $datadaftarpolirj_json = json_decode($item->datadaftarpolirj_json, true);

            if (
                isset($datadaftarpolirj_json['taskIdPelayanan']['taskId1']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId1'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId2']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId2'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId3']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId3'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId4']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId4'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId5']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId5'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId6']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId6'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId7']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId7'])
            ) {
                $startTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId6'], 'Asia/Jakarta');
                $endTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId7'], 'Asia/Jakarta');
                return $endTime->diffInMinutes($startTime);
            } else if (
                isset($datadaftarpolirj_json['taskIdPelayanan']['taskId1']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId1'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId2']) && empty($datadaftarpolirj_json['taskIdPelayanan']['taskId2'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId3']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId3'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId4']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId4'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId5']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId5'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId6']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId6'])
                && isset($datadaftarpolirj_json['taskIdPelayanan']['taskId7']) && !empty($datadaftarpolirj_json['taskIdPelayanan']['taskId7'])
            ) {
                $startTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId6'], 'Asia/Jakarta');
                $endTime = Carbon::createFromFormat('d/m/Y H:i:s', $datadaftarpolirj_json['taskIdPelayanan']['taskId7'], 'Asia/Jakarta');
                return $endTime->diffInMinutes($startTime);
            }
        });

        return $waktuLayananPoli ?? 0;
    }


    // LOG JKN_MOBILE
    ///////////////////////////////////////////////////////
    public function indexLogJknMobile(Request $request)
    {
        $date = $request->input('date') ? $request->input('date') : Carbon::now()->format('d/m/Y');
        $page = $request->input('page') ? $request->input('page') : 1;
        $show = $request->input('show') ? $request->input('show') : 10;
        $find = $request->input('find') ? $request->input('find') : '';


        $queryLogJknMobile = $this->queryLogJknMobile($date, $show);




        //return view
        return inertia('BPJS/LogJknMobile', [
            'date' => $date,
            'page' => $page,
            'show' => $show,
            'queryLogJknMobile' => $queryLogJknMobile,

        ]);
    }

    private function queryLogJknMobile($dateRef, $show = 10)
    {
        $myRefdate = $dateRef;
        $query = DB::table('api_log_status')
            ->select(
                DB::raw("to_char(datetime,'dd/mm/yyyy hh24:mi:ss') AS datetime"),
                DB::raw("to_char(datetime,'yyyymmddhh24miss') AS datetime1"),
                'request',
                'response'
            )
            ->where(DB::raw("to_char(datetime,'dd/mm/yyyy')"), '=', $myRefdate)
            ->orderBy('datetime1',  'desc')
            ->paginate($show);

        return $query;
    }
}
