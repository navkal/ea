import csv
from datetime import datetime, date, time, timedelta
import argparse

AllDates = []
def getDates(filename,outputfile):
    with open(outputfile, mode='w', newline="", encoding='utf-8') as w:
        csvwriter = csv.writer(w)
        csvwriter.writerow(['Date / Time', 'Name Path Reference', 'Object Name', 'Object Value'])
        n = 0
        oneday = timedelta(days=1)
        KWhtotal = 0
        KVAhTotal = 0
        with open(filename,mode='r') as f:
            csvfile = csv.reader(f)
            for row in csvfile:
                if(n == 0):
                    n += 1
                    TimeIndex = row[4:]
                    if '24:00:00' in TimeIndex:
                        for z in range(len(TimeIndex)):
                            if TimeIndex[z] == "24:00:00":
                                TimeIndex[z] = "00:00:00:01"
                    #print(row)
                elif(n > 0):
                    n += 1
                    account = row[0]
                    if row[1] == '':
                        continue
                    datesplit = row[1].split('/')
                    #print(int(datesplit[2]),int(datesplit[1]),int(datesplit[0]))
                    d = date(int(datesplit[2]),int(datesplit[0]),int(datesplit[1]))
                    units = row[3]
                    SpotMeasures = row[4:]
                    #print(SpotMeasures)
                    for x in range(len(TimeIndex)):
                        #print(TimeIndex[x])
                        timesplit = TimeIndex[x].split(':')
                        #print(timesplit)
                        t = time(int(timesplit[0]), int(timesplit[1]))
                        if len(timesplit) == 4:
                            d = oneday + d
                        if SpotMeasures[x] == '':
                            continue
                        if units == 'kWh':
                            KWhtotal += float(SpotMeasures[x])
                            dt = datetime.combine(d, t)
                            #print(dt)
                            NamePathRef = account + '.' + units
                            csvwriter.writerow([dt.strftime('%m/%d/%Y %H:%M'), NamePathRef, NamePathRef, SpotMeasures[x]])
                            csvwriter.writerow([dt.strftime('%m/%d/%Y %H:%M'), NamePathRef + '.sum', NamePathRef + '.sum', KWhtotal])
                        elif units == 'kVAh':
                            KVAhTotal += float(SpotMeasures[x])
                            dt = datetime.combine(d, t)
                            #print(dt)
                            NamePathRef = account + '.' + units
                            csvwriter.writerow([dt.strftime('%m/%d/%Y %H:%M'),NamePathRef,NamePathRef,SpotMeasures[x]])
                            csvwriter.writerow([dt.strftime('%m/%d/%Y %H:%M'),NamePathRef + '.sum',NamePathRef + '.sum',+ KVAhTotal])
                        elif units == 'Power Factor':
                            dt = datetime.combine(d, t)
                            #print(dt)
                            NamePathRef = account + '.' + units
                            csvwriter.writerow([dt.strftime('%m/%d/%Y %H:%M'), NamePathRef, NamePathRef, SpotMeasures[x]])

                if n > 3889838388:
                    break
                #print(n)


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description='converts NG output files to metasys filetype')
    parser.add_argument('-i', dest='input_file',  help='name of input file')
    parser.add_argument('-o', dest='output_file', help='name of output file')
    args = parser.parse_args()
    getDates(args.input_file,args.output_file)

#getDates('input/ngrid_3cad443c_046db54d_hourly.csv','archive/converted_1.csv')
#getDates('input/15-16 COMP.DATA-Sort.csv','archive/converted_2.csv')
