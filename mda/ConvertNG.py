import csv

AllDates = []
def getDates(filename,outputfile):
    with open(outputfile, mode='w', newline="", encoding='utf-8') as w:
        csvwriter = csv.writer(w)
        csvwriter.writerow(['Date / Time', 'Name Path Reference', 'Object Name', 'Object Value'])
        n = 0
        KWhtotal = 0
        KVAhTotal = 0
        with open(filename,mode='r') as f:
            csvfile = csv.reader(f)
            for row in csvfile:
                if(n == 0):
                    n += 1
                    TimeIndex =  row[4:]
                    if '24:00:00' in TimeIndex:
                        for z in range(len(TimeIndex)):
                            if TimeIndex[z] == "24:00:00":
                                TimeIndex[z] = "23:59"
                elif(n > 0):
                    n += 1
                    account = row[0]
                    date = row[1]
                    units = row[3]
                    SpotMeasures = row[4:]
                    print(SpotMeasures)
                    for x in range(len(TimeIndex)):
                        if SpotMeasures[x] == '':
                            continue
                        if units == 'kWh':
                            KWhtotal += float(SpotMeasures[x])
                            DateTime = date + ' ' + TimeIndex[x]
                            NamePathRef = account + '.' + units
                            csvwriter.writerow([DateTime, NamePathRef, NamePathRef, SpotMeasures[x]])
                            csvwriter.writerow([DateTime, NamePathRef + '.sum', NamePathRef + '.sum', KWhtotal])
                        elif units == 'kVAh':
                            KVAhTotal += float(SpotMeasures[x])
                            DateTime = date + ' ' + TimeIndex[x]
                            NamePathRef = account + '.' + units
                            csvwriter.writerow([DateTime,NamePathRef,NamePathRef,SpotMeasures[x]])
                            csvwriter.writerow([DateTime,NamePathRef + '.sum',NamePathRef + '.sum',+ KVAhTotal])
                        elif units == 'Power Factor':
                            DateTime = date + ' ' + TimeIndex[x]
                            NamePathRef = account + '.' + units
                            csvwriter.writerow([DateTime, NamePathRef, NamePathRef, SpotMeasures[x]])

                if n > 2222:
                    break
                print(n)


getDates('input/ngrid_3cad443c_046db54d_hourly.csv','archive/converted1.csv')
getDates('input/15-16 COMP.DATA-Sort.csv','archive/converted2.csv')
