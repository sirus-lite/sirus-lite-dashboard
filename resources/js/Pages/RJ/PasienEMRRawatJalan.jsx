import PageLayout from '@/Layouts/PageLayout';
import { Table, Badge } from 'flowbite-react';
import PaginationData from '@/Components/PaginationData';
import MyApexCharts from '@/Layouts/Chart/MyApexCharts';
import { useSelector } from 'react-redux';
import CrudTopBar from '@/Components/CrudTopBar';
import { useEffect } from 'react';
import { router } from '@inertiajs/react'


export default function PasienEMRRawatJalan(props) {
    const { auth, date, queryPasienEMRRJ, queryPasienEmrRJKelengkapanPengisianHarian } = props;




    const selector = useSelector((state) => state.filter);

    useEffect(() => {
        clearTimeout(window.dateRefimeout);
        window.dateRefimeout = setTimeout(() => {
            router.get(route(route().current()), { date: selector.filter.date || date, page: selector.filter.page, show: selector.filter.show }, { preserveState: true, replace: true, only: [] });
        }, 300);
    }, []);

    function TableDataRow(props) {
        const {
            item,
            index
        } = props;

        const datadaftar_json = JSON.parse(item?.datadaftarpolirj_json) || null;
        const anamnesa = (datadaftar_json?.anamnesa) ? 1 : 0 || 0
        const pemeriksaan = (datadaftar_json?.pemeriksaan) ? 1 : 0 || 0
        const penilaian = (datadaftar_json?.penilaian) ? 1 : 0 || 0
        const procedure = (datadaftar_json?.procedure) ? 1 : 0 || 0
        const diagnosis = (datadaftar_json?.diagnosis) ? 1 : 0 || 0
        const perencanaan = (datadaftar_json?.perencanaan) ? 1 : 0 || 0
        const prosentaseEMR =
            Math.floor(((anamnesa + pemeriksaan + penilaian + procedure + diagnosis + perencanaan) / 6) *
                100);

        const bgSelesaiPemeriksaan = (datadaftar_json?.perencanaan?.pengkajianMedis?.drPemeriksa) ? 'bg-green-100' : '' || ''
        const badgecolorEmr = prosentaseEMR >= 80 ? 'success' : 'failure';

        const badgecolorStatus = (item.rj_status === 'A'
            ? 'warning'
            : (item.rj_status === 'L'
                ? 'success'
                : (item.rj_status === 'I'
                    ? 'info'
                    : (item.rj_status === 'F'
                        ? 'failure'
                        : ''))))

        const badgecolorKlaim =
            item.klaim_id === 'UM'
                ? 'dark'
                : (item.klaim_id === 'JM'
                    ? 'success'
                    : (item.klaim_id === 'KR'
                        ? 'indigo'
                        : 'failure'));

        const badgecolorAdministrasiRj = datadaftar_json?.AdministrasiRj ? 'success' : 'failure';

        const badgecolorKeluhanUtama = datadaftar_json?.anamnesa?.keluhanUtama?.keluhanUtama ? 'success' : 'failure';
        const badgecolorTTV = datadaftar_json?.pemeriksaan?.tandaVital?.suhu ? 'success' : 'failure';
        const badgecolorTTDPerawat = datadaftar_json?.anamnesa?.pengkajianPerawatan?.perawatPenerima ? 'success' : 'failure';
        const badgecolorPemeriksaan = datadaftar_json?.pemeriksaan?.tandaVital?.keadaanUmum ? 'success' : 'failure';
        const badgecolorPenilaian = datadaftar_json?.penilaian ? 'success' : 'failure';
        const badgecolorDiagnosisText = datadaftar_json?.diagnosisFreeText ? 'success' : 'failure';
        const badgecolorDiagnosisICDX = datadaftar_json?.diagnosis?.length || 0 ? 'success' : 'failure';
        const badgecolorTerapi = datadaftar_json?.perencanaan?.terapi?.terapi ? 'success' : 'failure';
        const badgecolorEresep = datadaftar_json?.eresep ? 'success' : 'failure';

        const badgecolorTTDDokter = datadaftar_json?.perencanaan?.pengkajianMedis?.drPemeriksa ? 'success' : 'failure';
        const badgecolorTTDAdministrasi = badgecolorAdministrasiRj;
        const badgecolorTelaahObat = datadaftar_json?.telaahResep ? 'success' : 'failure';
        const badgecolorTelaahResep = datadaftar_json?.telaahObat ? 'success' : 'failure';
        const badgecolorKirimSatuSehat = datadaftar_json?.satuSehatUuidRJ?.length ? 'success' : 'failure';
        const badgecolorKirimDinkesTA = datadaftar_json?.satuDataKesehatanTulungagung ? 'success' : 'failure';

        return (
            <>
                {/* <Table.Row className={`bg-white dark:border-gray-700 dark:bg-gray-800 ${bgSelesaiPemeriksaan}`} key={'TableDataRow' + index} > */}
                <Table.Row className={`bg-white dark:border-gray-700 dark:bg-gray-800`} key={'TableDataRow' + index} >

                    <Table.Cell className="font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        <div className="">
                            {/* <div className="text-sm font-semibold text-gray-500 text-primary">
                                Record ke <span className='text-3xl text-gray-900'>{index + 1}</span>
                            </div> */}
                            <div className="font-semibold text-primary">
                                {item.reg_no}
                            </div>
                            <div className="font-semibold text-gray-900">
                                {item.reg_name + ' / (' + item.sex + ')' + ' / ' + item.thn}
                            </div>
                            <div className="font-normal text-gray-900">
                                {item.address}
                            </div>
                        </div>
                    </Table.Cell>
                    <Table.Cell>
                        <div className="">
                            <div className="font-semibold text-primary">
                                {item.poli_desc}
                            </div>
                            <div className="font-semibold text-gray-900">
                                {item.dr_name + ' / '}
                                <Badge color={badgecolorKlaim}>
                                    {item.klaim_id == 'UM'
                                        ? 'UMUM'
                                        : (item.klaim_id == 'JM'
                                            ? 'BPJS'
                                            : (item.klaim_id == 'KR'
                                                ? 'Kronis'
                                                : 'Asuransi Lain'))}
                                </Badge>

                            </div>
                            <div className="font-normal text-gray-900">
                                {'Nomer Pelayanan ' + item.no_antrian}
                            </div>
                            <div className="font-normal">
                                {item.vno_sep}
                            </div>
                        </div>
                    </Table.Cell>
                    <Table.Cell>
                        <div>
                            <div className="font-semibold text-primary">
                                {item.rj_date + ' / Shift' + item.shift}
                            </div>
                            <div className="flex italic font-semibold text-gray-900">
                                <Badge color={badgecolorStatus}>
                                    {(item.rj_status === 'A'
                                        ? 'Pelayanan '
                                        : (item.rj_status === 'L'
                                            ? 'Selesai Pelayanan '
                                            : (item.rj_status === 'I'
                                                ? 'Transfer Inap '
                                                : (item.rj_status === 'F'
                                                    ? 'Batal Transaksi '
                                                    : ''))))
                                    }
                                </Badge>

                                / Emr : <Badge color={badgecolorEmr}>{prosentaseEMR}%</Badge>
                                {/* {anamnesa} + {pemeriksaan} + {penilaian} + {procedure} + {diagnosis} + {perencanaan} */}

                            </div>
                            <div className="font-normal text-gray-900">
                                {'' + item.nobooking}
                            </div>

                            <div className="font-normal text-gray-700">
                                <Badge color={badgecolorAdministrasiRj}>
                                    Administrasi : {datadaftar_json?.AdministrasiRj?.userLog || ' ---'}
                                </Badge>
                            </div>

                        </div>
                    </Table.Cell>
                    <Table.Cell>
                        <div className='grid justify-center w-full grid-cols-3 gap-1 rounded-lg'>
                            <Badge color={badgecolorKeluhanUtama}>Keluhan Utama</Badge>
                            <Badge color={badgecolorTTV}>TTV</Badge>
                            <Badge color={badgecolorTTDPerawat}>TTD Perawat</Badge>
                            <Badge color={badgecolorPemeriksaan}>Pemeriksaan</Badge>
                            <Badge color={badgecolorPenilaian}>Penilaian</Badge>
                            <Badge color={badgecolorDiagnosisText}>Diagnosis Text</Badge>
                            <Badge color={badgecolorDiagnosisICDX}>Diagnosis ICDX</Badge>
                            <Badge color={badgecolorTerapi}>Terapi</Badge>
                            <Badge color={badgecolorEresep}>E-resep</Badge>
                            <Badge color={badgecolorTTDDokter}>TTD Dokter</Badge>
                            <Badge color={badgecolorTTDAdministrasi}>TTD Administrasi</Badge>
                            <Badge color={badgecolorTelaahObat}>Telaah Obat</Badge>
                            <Badge color={badgecolorTelaahResep}>Telaah Resep</Badge>
                            <Badge color={badgecolorKirimSatuSehat}>Kirim Satu Sehat</Badge>
                            <Badge color={badgecolorKirimDinkesTA}>Kirim DinkesTA</Badge>

                        </div>
                    </Table.Cell>
                    <Table.Cell>
                        {item.reg_name}
                    </Table.Cell>

                </Table.Row >
            </>
        );
    }


    function ChartUmumBpjs(props) {
        const { dateRef, data } = props;

        const jmlKunjungan = [data.queryTotal];
        const jmlKunjunganDesc = 'Total Kunjungan';

        const jmlKunjunganLengkap = [data.queryLengkap];
        const jmlKunjunganDescLengkap = 'Kelengkapan EMR';

        const jmlKelengkapanDiagnosis = [data.queryDiagnosisIcd];
        const jmlKelengkapanDescDiagnosis = 'Kelengkapan Diagnosis';

        const jmlKirimSS = [data.querySatuSehat];
        const jmlKirimDescSS = 'Kirim Satu Sehat';

        const rjDate = [dateRef];


        return (
            <div className='bg-white border border-gray-200 rounded-lg shadow-sm'>
                <div className='grid grid-cols-2 mx-2 '>
                    <MyApexCharts myType={'bar'} myWidth={'100%'} myCategories={rjDate} myData={[
                        {
                            data: jmlKunjungan,
                            name: jmlKunjunganDesc
                        },
                        {
                            data: jmlKunjunganLengkap,
                            name: jmlKunjunganDescLengkap
                        },
                        {
                            data: jmlKelengkapanDiagnosis,
                            name: jmlKelengkapanDescDiagnosis
                        },
                        {
                            data: jmlKirimSS,
                            name: jmlKirimDescSS
                        }
                    ]}
                        myChartTitle={'Rawat Jalan Tanggal ' + rjDate} />



                </div>
            </div>
        )
    }

    return (
        <PageLayout user={auth.user}>
            <div className='mb-4'>
                <ChartUmumBpjs
                    data={queryPasienEmrRJKelengkapanPengisianHarian}
                    dateRef={selector.filter.date} />
            </div>

            <div className='h-[calc(100vh-100px)]  p-4 bg-white border border-gray-200 rounded-lg shadow-sm '>




                <CrudTopBar date={date}></CrudTopBar>

                <div className='h-[calc(100vh-180px)] overflow-auto'>
                    <Table striped hoverable>
                        <Table.Head className='sticky top-0'>
                            <Table.HeadCell>Pasien</Table.HeadCell>
                            <Table.HeadCell>Poli</Table.HeadCell>
                            <Table.HeadCell>Status Layanan</Table.HeadCell>
                            <Table.HeadCell>Status EMR</Table.HeadCell>
                            <Table.HeadCell>Action</Table.HeadCell>

                        </Table.Head>
                        <Table.Body className="divide-y">
                            {queryPasienEMRRJ.data.map((item, index) => (
                                <TableDataRow item={item} index={index} key={index} />
                            ))}
                        </Table.Body>
                    </Table>

                    <div className='sticky bottom-0 flex justify-end rounded-b-lg bg-gray-50'>
                        <PaginationData class="mt-6" links={queryPasienEMRRJ.links} total={queryPasienEMRRJ.total} from={queryPasienEMRRJ.from} to={queryPasienEMRRJ.to} current_page={queryPasienEMRRJ.current_page} last_page={queryPasienEMRRJ.last_page} />
                    </div>
                </div>

            </div>

        </PageLayout>

    );
}
